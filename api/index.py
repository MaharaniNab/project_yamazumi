import os
import cv2
import time
import math
import threading
import io
import uuid
import re
import copy
import datetime
import json
import numpy as np
import mediapipe as mp
import pandas as pd
import matplotlib
import base64

matplotlib.use('Agg')
import matplotlib.pyplot as plt
from flask import Flask, request, jsonify, current_app
from flask_cors import CORS
from werkzeug.utils import secure_filename
from werkzeug.security import generate_password_hash, check_password_hash
from flask_sqlalchemy import SQLAlchemy

# ==============================================================================
# 1. KONFIGURASI APLIKASI
# ==============================================================================

# Gunakan path absolut agar aman saat deploy di Railway/server lain
BASE_DIR      = os.path.dirname(os.path.abspath(__file__))
UPLOAD_FOLDER = os.path.join(BASE_DIR, 'uploads')
STATIC_FOLDER = os.path.join(BASE_DIR, 'static')
DB_PATH       = os.path.join(BASE_DIR, 'db_production.sqlite')

JAM_KERJA_DETIK = 25920.0  # 7.2 Jam Kerja Efektif

app = Flask(__name__)

# --- CORS: izinkan request dari domain Laravel Anda ---
# Ganti LARAVEL_URL dengan URL Vercel Anda, misalnya: https://project-yamazumi.vercel.app
LARAVEL_URL = os.environ.get('LARAVEL_URL', '*')
CORS(app, resources={r"/api/*": {"origins": LARAVEL_URL}})

app.config['UPLOAD_FOLDER']             = UPLOAD_FOLDER
app.config['STATIC_FOLDER']             = STATIC_FOLDER
app.config['SQLALCHEMY_DATABASE_URI']   = f'sqlite:///{DB_PATH}'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['MAX_CONTENT_LENGTH']        = 500 * 1024 * 1024  # Max upload 500MB

db = SQLAlchemy(app)

os.makedirs(UPLOAD_FOLDER, exist_ok=True)
os.makedirs(STATIC_FOLDER, exist_ok=True)

# ==============================================================================
# 2. DATABASE MODELS (tidak diubah dari versi asli)
# ==============================================================================

class AnalysisHistory(db.Model):
    id           = db.Column(db.String(50), primary_key=True)
    timestamp    = db.Column(db.DateTime, default=datetime.datetime.now)
    line_name    = db.Column(db.String(50))
    part_name    = db.Column(db.String(100))
    pic_name     = db.Column(db.String(100))
    efficiency   = db.Column(db.Float)
    output_daily = db.Column(db.Integer)
    cycle_time   = db.Column(db.Float)
    full_data_json = db.Column(db.Text)

class JobQueue(db.Model):
    id             = db.Column(db.String(50), primary_key=True)
    status         = db.Column(db.String(20), default='processing')
    created_at     = db.Column(db.DateTime, default=datetime.datetime.now)
    updated_at     = db.Column(db.DateTime, default=datetime.datetime.now, onupdate=datetime.datetime.now)
    metadata_json  = db.Column(db.Text)
    video_map_json = db.Column(db.Text)
    takt_time      = db.Column(db.Float)
    mp_aktual      = db.Column(db.Integer)
    error_message  = db.Column(db.Text, nullable=True)
    result_json    = db.Column(db.Text, nullable=True)

# ==============================================================================
# 3. KONFIGURASI ELEMEN KERJA (tidak diubah)
# ==============================================================================
ELEMENT_CLASSIFICATION = {
    "Proses Jahit":       ('#1a9850', 'Value-Added'),
    "Pengecekan Barang":  ('#fee08b', 'Necessary but Non-Value-Added'),
    "Mengambil Produk":   ('#d73027', 'Non-Value-Added'),
    "Meletakkan Barang":  ('#d73027', 'Non-Value-Added'),
    "Mengganti Benang":   ('#f46d43', 'Non-Value-Added'),
    "Proses Menggosok":   ('#1a9850', 'Value-Added'),
    "Loading Mesin":      ('#1a9850', 'Value-Added'),
    "Menata Kain":        ('#fee08b', 'Necessary but Non-Value-Added'),
    "Proses Menggunting": ('#1a9850', 'Value-Added'),
    "Marking/Menandai":   ('#fee08b', 'Necessary but Non-Value-Added'),
    "Mesin Pressing":     ('#1a9850', 'Value-Added'),
    "Unloading Mesin":    ('#d73027', 'Non-Value-Added'),
    "Persiapan":          ('#d73027', 'Non-Value-Added'),
    "Lainnya":            ('#4575b4', 'Non-Value-Added'),
}
DEFAULT_COLOR = ('#4575b4', 'Non-Value-Added')

# ==============================================================================
# 4. LOGIKA COMPUTER VISION (tidak diubah dari versi asli Anda)
# ==============================================================================

def calculate_angle(a, b, c):
    a, b, c = np.array(a), np.array(b), np.array(c)
    radians = np.arctan2(c[1]-b[1], c[0]-b[0]) - np.arctan2(a[1]-b[1], a[0]-b[0])
    angle = np.abs(radians*180.0/np.pi)
    if angle > 180.0: angle = 360 - angle
    return angle

def calculate_velocity(current_pos, prev_pos, fps):
    if prev_pos is None: return 0.0
    dist = np.linalg.norm(np.array(current_pos) - np.array(prev_pos))
    return dist * fps

def analyze_sewing_logic(kin, w, h):
    machine_center_x = w * 0.5
    machine_center_y = h * 0.6
    lx, ly = kin['pos_left']; rx, ry = kin['pos_right']
    if rx < 10 or ry < 10: rx, ry = machine_center_x, machine_center_y
    dist_left  = math.hypot(lx - machine_center_x, ly - machine_center_y)
    dist_right = math.hypot(rx - machine_center_x, ry - machine_center_y)
    avg_dist = (dist_left + dist_right) / 2
    SEWING_RADIUS   = w * 0.25
    REACHING_RADIUS = w * 0.45
    if avg_dist < SEWING_RADIUS: return "Proses Jahit"
    elif avg_dist < REACHING_RADIUS: return "Menata Kain"
    else:
        if lx < (w * 0.2) or ly > (h * 0.8): return "Mengambil Produk"
        elif rx > (w * 0.8): return "Meletakkan Barang"
        return "Mengambil Produk"

def analyze_ironing_logic(kin, w, h):
    TABLE_Y_TOP = h * 0.4
    lx, ly = kin['pos_left']; rx, ry = kin['pos_right']
    vr = kin['v_right']
    if ry > TABLE_Y_TOP:
        if vr > 80: return "Proses Menggosok"
        else: return "Menata Kain"
    elif lx < (w * 0.25): return "Mengambil Produk"
    elif rx > (w * 0.75): return "Meletakkan Barang"
    return "Menata Kain"

def analyze_side_loading_logic(kin, w, h):
    MACHINE_ENTRY_X = w * 0.50
    PICKUP_AREA_X   = w * 0.35
    lx, ly = kin['pos_left']; rx, ry = kin['pos_right']
    if rx > MACHINE_ENTRY_X or lx > MACHINE_ENTRY_X:
        if ry < (h * 0.85): return "Loading Mesin"
    if lx < PICKUP_AREA_X: return "Mengambil Produk"
    return "Persiapan"

def analyze_pressing_logic(kin, w, h):
    MACHINE_BED_TOP    = h * 0.5
    MACHINE_BED_BOTTOM = h * 0.9
    MACHINE_CENTER_X   = w * 0.5
    MACHINE_WIDTH_RADIUS = w * 0.25
    lx, ly = kin['pos_left']; rx, ry = kin['pos_right']
    l_in = (MACHINE_BED_TOP < ly < MACHINE_BED_BOTTOM) and (abs(lx - MACHINE_CENTER_X) < MACHINE_WIDTH_RADIUS)
    r_in = (MACHINE_BED_TOP < ry < MACHINE_BED_BOTTOM) and (abs(rx - MACHINE_CENTER_X) < MACHINE_WIDTH_RADIUS)
    if l_in or r_in: return "Loading Mesin"
    elif ly < MACHINE_BED_TOP and ry < MACHINE_BED_TOP: return "Mesin Pressing"
    elif lx < (w * 0.2): return "Unloading Mesin"
    elif rx > (w * 0.8): return "Unloading Mesin"
    return "Mesin Pressing"

def analyze_cutting_logic(kin, w, h):
    rx = kin['pos_right'][0]; lx = kin['pos_left'][0]
    c1, c2 = w * 0.2, w * 0.8
    if c1 < rx < c2: return "Proses Menggunting"
    elif rx > c2: return "Marking/Menandai"
    elif lx < c1: return "Mengambil Produk"
    return "Lainnya"

def run_single_video_analysis(video_path, filename_context=""):
    cap = cv2.VideoCapture(video_path)
    if not cap.isOpened(): return [], [], 0.0

    SKIP_FRAMES  = 2
    TARGET_WIDTH = 640

    fps    = cap.get(cv2.CAP_PROP_FPS)
    orig_w = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
    orig_h = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
    resize_ratio = TARGET_WIDTH / orig_w
    w, h = TARGET_WIDTH, int(orig_h * resize_ratio)

    mp_pose = mp.solutions.pose
    pose = mp_pose.Pose(
        min_detection_confidence=0.3,
        min_tracking_confidence=0.3,
        model_complexity=2,
        smooth_landmarks=True
    )

    prev_l, prev_r = None, None
    curr_status        = "Persiapan"
    last_valid_status  = "Persiapan"
    no_detection_frames = 0
    MAX_MISSING_TOLERANCE = 15

    raw_durations  = []
    start_vid_time = 0.0
    frame_count    = 0

    fname = filename_context.lower()
    ptype = "sewing"
    if "gosok" in fname:   ptype = "ironing"
    elif "fusing" in fname: ptype = "fusing"
    elif "press" in fname:  ptype = "pressing"
    elif "gunting" in fname or "cut" in fname: ptype = "cutting"

    while cap.isOpened():
        ret, frame = cap.read()
        if not ret: break

        if frame_count % (SKIP_FRAMES + 1) != 0:
            frame_count += 1; continue
        frame_count += 1

        curr_vid_time = cap.get(cv2.CAP_PROP_POS_MSEC) / 1000.0

        img = cv2.resize(frame, (w, h))
        img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        img.flags.writeable = False
        results = pose.process(img)

        new_status = None

        try:
            if results.pose_landmarks:
                no_detection_frames = 0
                lm = results.pose_landmarks.landmark
                lw = [lm[mp_pose.PoseLandmark.LEFT_WRIST].x * w,  lm[mp_pose.PoseLandmark.LEFT_WRIST].y * h]
                rw = [lm[mp_pose.PoseLandmark.RIGHT_WRIST].x * w, lm[mp_pose.PoseLandmark.RIGHT_WRIST].y * h]

                if lw[0] < 0: lw = [lm[mp_pose.PoseLandmark.LEFT_ELBOW].x * w,  lm[mp_pose.PoseLandmark.LEFT_ELBOW].y * h]
                if rw[0] < 0: rw = [lm[mp_pose.PoseLandmark.RIGHT_ELBOW].x * w, lm[mp_pose.PoseLandmark.RIGHT_ELBOW].y * h]

                vl = calculate_velocity(lw, prev_l, fps)
                vr = calculate_velocity(rw, prev_r, fps)
                dist_hands = math.hypot(lw[0]-rw[0], lw[1]-rw[1])

                kin = {'pos_left': lw, 'pos_right': rw, 'v_left': vl, 'v_right': vr, 'dist_hands': dist_hands}
                prev_l, prev_r = lw, rw

                if ptype == "sewing":   new_status = analyze_sewing_logic(kin, w, h)
                elif ptype == "ironing": new_status = analyze_ironing_logic(kin, w, h)
                elif ptype == "fusing":  new_status = analyze_side_loading_logic(kin, w, h)
                elif ptype == "pressing": new_status = analyze_pressing_logic(kin, w, h)
                elif ptype == "cutting":  new_status = analyze_cutting_logic(kin, w, h)

                last_valid_status = new_status
            else:
                no_detection_frames += 1
                if no_detection_frames < MAX_MISSING_TOLERANCE: new_status = last_valid_status
                else: new_status = "Lainnya"
        except Exception:
            new_status = last_valid_status

        if new_status and new_status != curr_status:
            dur = curr_vid_time - start_vid_time
            if dur > 0.5: raw_durations.append({'activity': curr_status, 'duration': dur})
            curr_status    = new_status
            start_vid_time = curr_vid_time

    final_time = cap.get(cv2.CAP_PROP_POS_MSEC) / 1000.0
    dur = final_time - start_vid_time
    if dur > 0.5: raw_durations.append({'activity': curr_status, 'duration': dur})
    cap.release(); pose.close()

    df = pd.DataFrame(raw_durations)
    if df.empty: return [], [], 0.0

    stats_res  = []
    total_mean = 0.0
    grouped = df.groupby('activity')['duration'].agg(['mean', 'std', 'count', 'sum']).reset_index()
    for _, row in grouped.iterrows():
        stats_res.append({
            "elemen_kerja":  row['activity'],
            "durasi_detik":  round(row['mean'], 2),
            "std_dev":       round(row['std'] if not pd.isna(row['std']) else 0, 2),
            "frekuensi":     row['count'],
            "total_durasi":  round(row['sum'], 2)
        })
        total_mean += row['mean']
    return stats_res, raw_durations, round(total_mean, 2)

# ==============================================================================
# 5. SIMULASI KAIZEN & SAVE DB (tidak diubah dari versi asli)
# ==============================================================================

NVA_ELIMINATION        = 1.00
NVA_DOMINANT_THRESHOLD = 0.20
CV_MEDIUM_RISK         = 5.0
CV_HIGH_RISK           = 10.0
TAKT_TOLERANCE         = 0.10

def classify_station_status(ct, takt):
    if ct > takt:                                 return "Bottleneck"
    elif ct > takt * (1 - TAKT_TOLERANCE):        return "At-Risk"
    elif ct >= takt * (1 - 2 * TAKT_TOLERANCE):   return "Balanced"
    else:                                          return "Underloaded"

def compute_station_cv(elements):
    total_ct = sum(e['durasi_detik'] for e in elements)
    if total_ct <= 0: return 0.0
    sum_var = sum((e.get('std_dev', 0) ** 2) for e in elements)
    sigma = math.sqrt(sum_var)
    return round((sigma / total_ct) * 100, 2)

def compute_nva_pct(elements):
    total_ct = sum(e['durasi_detik'] for e in elements)
    if total_ct <= 0: return 0.0
    nva_total = sum(
        e['durasi_detik'] for e in elements
        if ELEMENT_CLASSIFICATION.get(e['elemen_kerja'], DEFAULT_COLOR)[1] == "Non-Value-Added"
    )
    return nva_total / total_ct

def allocate_manpower(new_durations, takt, mp_aktual, nva_pcts):
    n_stations  = len(new_durations)
    mp_assigned = {s: 1 for s in new_durations}
    remaining   = max(0, mp_aktual - n_stations)

    bottlenecks = [(s, ct) for s, ct in new_durations.items() if ct > takt]
    bottlenecks.sort(key=lambda x: (
        0 if nva_pcts.get(x[0], 0) > NVA_DOMINANT_THRESHOLD else 1,
        -x[1]
    ))

    for s_name, ct in bottlenecks:
        while remaining > 0 and (ct / mp_assigned[s_name]) > takt:
            mp_assigned[s_name] += 1
            remaining -= 1

    while remaining > 0:
        most_loaded = max(new_durations.keys(), key=lambda s: new_durations[s] / mp_assigned[s])
        mp_assigned[most_loaded] += 1
        remaining -= 1

    result = {}
    for s_name, ct in new_durations.items():
        mp     = mp_assigned[s_name]
        ct_ef  = round(ct / mp, 2)
        mp_bal = round((ct_ef / takt) * 100, 1) if takt > 0 else 0
        nva_dom = nva_pcts.get(s_name, 0) > NVA_DOMINANT_THRESHOLD
        result[s_name] = {
            "ct_after":       round(ct, 2),
            "mp_assigned":    mp,
            "ct_efektif":     ct_ef,
            "mp_balance_pct": mp_bal,
            "is_nva_dominant": nva_dom,
            "nva_pct":        round(nva_pcts.get(s_name, 0) * 100, 1),
            "utilized": (
                "Optimal"       if mp_bal >= 90 else
                "Baik"          if mp_bal >= 75 else
                "Underutilized"
            )
        }
    return result

def run_robust_balancing_simulation(original_job_data, mp_aktual=None):
    job  = copy.deepcopy(original_job_data)
    takt = float(job.get("takt_time", 0))
    station_profiles = job.get("station_profiles", {})

    if mp_aktual is None:
        mp_aktual = int(job.get("metadata", {}).get("mp_aktual", 0) or 0)
    mp_aktual = max(0, int(mp_aktual))

    stations_all  = list(job["detailed_results"].items())
    station_order = []
    nva_pcts      = {}

    for s_name, elements in stations_all:
        ct     = job["video_durations"].get(s_name, sum(e['durasi_detik'] for e in elements))
        cv     = station_profiles.get(s_name, {}).get("cv_persen", compute_station_cv(elements))
        status = classify_station_status(ct, takt) if takt > 0 else "Unknown"
        nva_p  = compute_nva_pct(elements)
        nva_pcts[s_name] = nva_p
        station_order.append({
            "name": s_name, "ct": ct, "cv": cv,
            "status": status, "nva_pct": nva_p, "elements": elements
        })

    bottlenecks    = sorted([s for s in station_order if s["status"] == "Bottleneck"], key=lambda x: -x["cv"])
    at_risks       = sorted([s for s in station_order if s["status"] == "At-Risk"],    key=lambda x: -x["cv"])
    others         = [s for s in station_order if s["status"] not in ("Bottleneck", "At-Risk")]
    priority_queue = bottlenecks + at_risks

    new_detailed    = {}
    new_durations   = {}
    kaizen_log      = []
    total_saving    = 0.0
    total_ct_before = 0.0
    total_ct_after  = 0.0

    priority_num = 1
    for st in priority_queue:
        s_name        = st["name"]
        s_status      = st["status"]
        optimized     = []
        s_total_after = 0.0

        for el in st["elements"]:
            orig     = el['durasi_detik']
            nama     = el['elemen_kerja']
            std      = el.get('std_dev', 0)
            total_ct_before += orig
            kategori = ELEMENT_CLASSIFICATION.get(nama, DEFAULT_COLOR)[1]
            saving   = 0.0

            if kategori == "Non-Value-Added":
                saving = orig * NVA_ELIMINATION

            new_dur = max(0.0, orig - saving)

            if saving > 0:
                total_saving += saving
                new_std = round(std * (new_dur / orig), 2) if orig > 0 else 0
                el['durasi_detik'] = round(new_dur, 2)
                el['std_dev']      = new_std
                kaizen_log.append({
                    "priority":        priority_num,
                    "station":         s_name,
                    "status":          s_status,
                    "cv_persen":       st["cv"],
                    "nva_pct":         round(st["nva_pct"] * 100, 1),
                    "is_nva_dominant": st["nva_pct"] > NVA_DOMINANT_THRESHOLD,
                    "elemen":          nama,
                    "kategori":        kategori,
                    "dur_before":      orig,
                    "saving":          round(saving, 2),
                    "dur_after":       round(new_dur, 2),
                    "pct":             "−100%",
                    "metode":          "Eliminasi NVA 100% — waste removal"
                })

            if el['durasi_detik'] > 0.01:
                optimized.append(el)
                s_total_after += el['durasi_detik']

        priority_num         += 1
        new_detailed[s_name]  = optimized
        new_durations[s_name] = round(s_total_after, 2)
        total_ct_after       += s_total_after

    for st in others:
        s_name = st["name"]
        ct_val = job["video_durations"].get(s_name, sum(e['durasi_detik'] for e in st["elements"]))
        new_detailed[s_name]  = st["elements"]
        new_durations[s_name] = round(ct_val, 2)
        total_ct_before      += ct_val
        total_ct_after       += ct_val

    n_stations  = len(new_durations)
    new_neck    = max(new_durations.values()) if new_durations else 0.0
    lb_after    = (total_ct_after / (n_stations * new_neck)) * 100 if new_neck > 0 else 0
    bd_after    = 100.0 - lb_after
    si_after    = math.sqrt(sum((new_neck - ct) ** 2 for ct in new_durations.values()))
    op_teoritis = total_ct_after / takt if takt > 0 else 0

    if mp_aktual == 0:
        mp_aktual = math.ceil(op_teoritis)

    mp_balancing = allocate_manpower(new_durations, takt, mp_aktual, nva_pcts)
    total_mp     = sum(v["mp_assigned"] for v in mp_balancing.values())
    overall_mp_b = (total_ct_after / (total_mp * takt)) * 100 if (takt > 0 and total_mp > 0) else 0

    op_min   = math.ceil(op_teoritis)
    op_spare = mp_aktual - op_min
    if op_spare > 0:
        mp_rekomendasi = (f"Line dapat berjalan optimal dengan {op_min} operator "
                          f"(saat ini {mp_aktual}, potensi hemat {op_spare} operator).")
    elif op_spare == 0:
        mp_rekomendasi = f"Jumlah {mp_aktual} operator sudah sesuai kebutuhan teoritis."
    else:
        mp_rekomendasi = (f"Jumlah operator kurang: butuh minimal {op_min}, "
                          f"tersedia {mp_aktual} (kekurangan {abs(op_spare)} operator).")

    old_sum = job.get("summary", {})
    def _parse(val, suffix):
        try: return float(str(val).replace(suffix, ""))
        except Exception: return 0.0

    lb_before   = _parse(old_sum.get("Presentase Line Balance", "0%"), "%")
    neck_before = _parse(old_sum.get("Neck Time", "0s"), "s")
    ct_b_sum    = _parse(old_sum.get("Total Cycle Time", "0s"), "s")
    bd_before   = 100.0 - lb_before

    target_harian        = float(job['metadata'].get('output_harian', 0))
    new_line_output_hari = int(JAM_KERJA_DETIK / new_neck) if new_neck > 0 else 0

    simulated_summary = {
        "Total Proses (Operator)": n_stations,
        "MP Aktual Input":         mp_aktual,
        "Op. Teoritis":            round(op_teoritis, 2),
        "Total MP Assigned":       total_mp,
        "Overall MP Balance":      f"{overall_mp_b:.1f}%",
        "Rekomendasi MP":          mp_rekomendasi,
        "Total Cycle Time":        f"{total_ct_after:.2f}s",
        "Neck Time":               f"{new_neck:.2f}s",
        "Presentase Line Balance": f"{lb_after:.2f}%",
        "Balance Delay":           f"{bd_after:.2f}%",
        "Smoothness Index":        f"{si_after:.2f}",
        "Takt Time":               f"{takt:.2f}s",
        "Target Harian":           int(target_harian),
        "Line Output Hari":        new_line_output_hari,
        "Total Saving NVA":        f"{total_saving:.2f}s",
    }

    simulated_station_performance = {
        s: {
            "output_jam":  round(3600 / dur, 1) if dur > 0 else 0,
            "output_hari": int(JAM_KERJA_DETIK / dur) if dur > 0 else 0
        }
        for s, dur in new_durations.items()
    }

    comp = {
        "Neck Time":          (f"{neck_before:.2f}s",   f"{new_neck:.2f}s"),
        "Line Efficiency":    (f"{lb_before:.2f}%",     f"{lb_after:.2f}%"),
        "Balance Delay":      (f"{bd_before:.2f}%",     f"{bd_after:.2f}%"),
        "Total Cycle Time":   (f"{ct_b_sum:.2f}s",      f"{total_ct_after:.2f}s"),
        "Total NVA Saving":   ("—",                     f"{total_saving:.2f}s"),
        "Op. Teoritis":       ("—",                     f"{op_teoritis:.2f}"),
        "Overall MP Balance": (f"Input: {mp_aktual} op", f"{overall_mp_b:.1f}%"),
    }

    rekomendasi_list = [
        f"SUMMARY: NVA saving={total_saving:.2f}s · "
        f"LE {lb_before:.1f}%→{lb_after:.1f}% · "
        f"BD {bd_before:.1f}%→{bd_after:.1f}% · "
        f"Op.teoritis={op_teoritis:.2f}, input={mp_aktual} · {mp_rekomendasi}"
    ]
    for k in kaizen_log:
        dom = " [NVA-Dom✓]" if k["is_nva_dominant"] else ""
        rekomendasi_list.append(
            f"[P{k['priority']}] {k['station']} ({k['status']}, CV={k['cv_persen']}%,"
            f" NVA={k['nva_pct']}%{dom}) — "
            f"{k['elemen']}: {k['dur_before']}s→{k['dur_after']}s (saving {k['saving']}s)"
        )

    return (rekomendasi_list, new_durations, new_detailed, comp,
            simulated_summary, simulated_station_performance,
            kaizen_log, mp_balancing, overall_mp_b)

def save_analysis_to_db(job_id, job_data, user_name):
    try:
        json_data = json.dumps(job_data)
        history = AnalysisHistory(
            id=job_id,
            line_name=job_data['metadata']['nama_line'],
            part_name=job_data['metadata']['nama_bagian'],
            pic_name=user_name,
            efficiency=float(job_data['summary']['Presentase Line Balance'].replace('%', '')),
            output_daily=int(job_data['summary']['Line Output Hari']),
            cycle_time=float(job_data['summary']['Total Cycle Time'].replace('s', '')),
            full_data_json=json_data
        )
        db.session.add(history)
        db.session.commit()
    except Exception as e:
        print(f"DB Error: {e}")

def create_yamazumi_chart_multi(job_id, video_durations, detailed_results, takt_time):
    job_names = list(video_durations.keys())
    fig, ax   = plt.subplots(figsize=(max(10, len(job_names) * 0.8), 7))
    legend_handles = {}

    for job_name in job_names:
        elements = detailed_results[job_name]
        bottom = 0
        for el in elements:
            duration = el['durasi_detik']
            label    = el['elemen_kerja']
            c_data   = ELEMENT_CLASSIFICATION.get(label, DEFAULT_COLOR)
            color, classification = c_data[0], c_data[1]
            if classification not in legend_handles:
                legend_handles[classification] = plt.Rectangle((0, 0), 1, 1, color=color)
            ax.bar(job_name, duration, bottom=bottom, color=color, edgecolor='white')
            bottom += duration
        ax.text(job_name, bottom + 1, f'{bottom:.1f}', ha='center', va='bottom', fontsize=9)

    ax.axhline(takt_time, color='red', linestyle='--', linewidth=2, label=f'Takt Time: {takt_time:.2f}s')
    ax.legend(
        [legend_handles[k] for k in sorted(legend_handles.keys())] + [ax.get_lines()[0]],
        sorted(legend_handles.keys()) + [f'Takt Time: {takt_time:.2f}s'],
        loc='center left', bbox_to_anchor=(1, 0.5)
    )
    ax.set_title('Yamazumi Chart')
    plt.tight_layout(rect=[0, 0, 0.85, 1])

    buf = io.BytesIO()
    plt.savefig(buf, format='png')
    plt.close(fig)
    buf.seek(0)
    img_base64 = base64.b64encode(buf.read()).decode('utf-8')
    return img_base64

# ==============================================================================
# 6. BACKGROUND JOB RUNNER
# ==============================================================================

def run_analysis_job_db(job_id, user_name, app_context):
    with app_context.app_context():
        job_record = JobQueue.query.get(job_id)
        if not job_record:
            return

        job = {
            "metadata":  json.loads(job_record.metadata_json),
            "video_map": json.loads(job_record.video_map_json),
            "takt_time": job_record.takt_time,
            "mp_aktual": job_record.mp_aktual
        }

        durations, detailed = {}, {}
        station_perf = {}
        total_time   = 0.0
        target_harian = float(job['metadata'].get('output_harian', 0))

        try:
            for name, path in job["video_map"].items():
                fname = os.path.basename(path)
                els, raw, dur = run_single_video_analysis(path, fname)
                detailed[name] = els
                durations[name] = dur
                total_time += dur

                out_jam  = (3600 / dur) if dur > 0 else 0
                out_hari = (JAM_KERJA_DETIK / dur) if dur > 0 else 0
                station_perf[name] = {'output_jam': round(out_jam, 1), 'output_hari': int(out_hari)}

            num_proc = len(job["video_map"])
            neck     = max(durations.values()) if durations else 0
            lb       = (total_time / (neck * num_proc)) * 100 if neck > 0 else 0
            line_out = int(JAM_KERJA_DETIK / neck) if neck > 0 else 0

            station_profiles = {}
            takt_val = float(job.get("takt_time", 0))
            for s_name, els in detailed.items():
                ct_val  = durations.get(s_name, 0)
                sum_var = sum((e.get("std_dev", 0) ** 2) for e in els)
                sigma   = math.sqrt(sum_var)
                robust_ct = ct_val + 2 * sigma
                cv_pct  = round((sigma / ct_val) * 100, 2) if ct_val > 0 else 0.0
                status  = classify_station_status(ct_val, takt_val) if takt_val > 0 else "Unknown"
                idle_time = max(0, takt_val - ct_val)
                overflow  = max(0, robust_ct - takt_val)
                station_profiles[s_name] = {
                    "mean_ct":          round(ct_val, 2),
                    "sigma":            round(sigma, 2),
                    "robust_ct":        round(robust_ct, 2),
                    "cv_persen":        cv_pct,
                    "idle_time":        round(idle_time, 2),
                    "overflow_robust":  round(overflow, 2),
                    "risk_category":    "High Risk" if cv_pct >= 10 else ("Medium Risk" if cv_pct >= 5 else "Low Risk"),
                    "status":           status,
                }

            bd = 100.0 - lb
            si = math.sqrt(sum((neck - ct)**2 for ct in durations.values())) if durations else 0.0

            job["summary"] = {
                "Total Proses (Operator)": num_proc,
                "Total Cycle Time":        f"{total_time:.2f}s",
                "Neck Time":               f"{neck:.2f}s",
                "Presentase Line Balance":  f"{lb:.2f}%",
                "Balance Delay":           f"{bd:.2f}%",
                "Smoothness Index":        f"{si:.2f}",
                "Takt Time":               f"{job['takt_time']:.2f}s",
                "Target Harian":           int(target_harian),
                "Line Output Hari":        line_out
            }
            job["chart_base64"]          = create_yamazumi_chart_multi(job_id, durations, detailed, job["takt_time"])
            job["detailed_results"]      = detailed
            job["video_durations"]       = durations
            job["station_performance"]   = station_perf
            job["station_profiles"]      = station_profiles
            job["status"]                = "completed"

            job_record.status      = 'completed'
            job_record.result_json = json.dumps(job)
            db.session.commit()

            save_analysis_to_db(job_id, job, user_name)

        except Exception as e:
            job_record.status        = 'failed'
            job_record.error_message = str(e)
            db.session.commit()
            print(f"[ERROR] Job {job_id} gagal: {e}")

# ==============================================================================
# 7. REST API & ROUTES
# ==============================================================================

@app.route('/health', methods=['GET'])
def health_check():
    """Endpoint untuk Railway health check"""
    return jsonify({"status": "ok", "service": "Yamazumi Flask API"}), 200

@app.route('/api/upload', methods=['POST'])
def api_upload():
    if 'file_list' not in request.files:
        return jsonify({"error": "Tidak ada file video yang diunggah."}), 400

    files     = request.files.getlist("file_list")
    out       = float(request.form.get("output_harian", 0))
    mp_aktual = int(request.form.get("mp_aktual", 0))
    takt      = JAM_KERJA_DETIK / out if out > 0 else 0

    jid   = str(uuid.uuid4().hex[:10])
    jpath = os.path.join(app.config['UPLOAD_FOLDER'], jid)
    os.makedirs(jpath, exist_ok=True)

    vmap = {}
    for f in files:
        if f.filename == '': continue
        name = secure_filename(f.filename)
        path = os.path.join(jpath, name)
        f.save(path)
        vmap[os.path.splitext(name)[0]] = path

    if not vmap:
        return jsonify({"error": "File video tidak valid."}), 400

    metadata_dict = request.form.to_dict()

    try:
        new_job = JobQueue(
            id=jid,
            status='processing',
            metadata_json=json.dumps(metadata_dict),
            video_map_json=json.dumps(vmap),
            takt_time=takt,
            mp_aktual=mp_aktual
        )
        db.session.add(new_job)
        db.session.commit()

        threading.Thread(
            target=run_analysis_job_db,
            args=(jid, "Laravel_System", current_app._get_current_object())
        ).start()

        return jsonify({
            "message": "Proses analisis berhasil masuk antrean",
            "job_id":  jid,
            "status":  "processing"
        }), 202
    except Exception as e:
        return jsonify({"error": f"Gagal menyimpan job: {str(e)}"}), 500

@app.route('/api/results/<job_id>', methods=['GET'])
def api_results(job_id):
    job_record = JobQueue.query.get(job_id)
    if not job_record:
        return jsonify({"error": "Data Job ID tidak ditemukan"}), 404

    if job_record.status != 'completed':
        return jsonify({
            "job_id":        job_record.id,
            "status":        job_record.status,
            "error_message": job_record.error_message
        }), 200

    result_data = json.loads(job_record.result_json)
    result_data["job_id"] = job_id
    result_data["status"] = "completed"
    return jsonify(result_data), 200

@app.route('/api/simulate/<job_id>', methods=['GET'])
def api_simulate(job_id):
    job_record = JobQueue.query.get(job_id)
    if not job_record or job_record.status != 'completed':
        return jsonify({"error": "Data simulasi tidak ditemukan atau belum selesai diproses"}), 404

    job       = json.loads(job_record.result_json)
    mp_aktual = int(job.get("mp_aktual") or job.get("metadata", {}).get("mp_aktual", 0) or 0)

    (rek, new_dur, new_el, comp, sim_sum, sim_perf,
     kaizen_log, mp_balancing, overall_mp_bal) = run_robust_balancing_simulation(job, mp_aktual)

    return jsonify({
        "job_id":                        job_id,
        "rekomendasi_list":              rek,
        "new_summary_comparison":        comp,
        "simulated_results":             new_el,
        "simulated_summary":             sim_sum,
        "simulated_station_performance": sim_perf,
        "kaizen_log":                    kaizen_log,
        "mp_balancing":                  mp_balancing,
        "overall_mp_bal":                round(overall_mp_bal, 1)
    }), 200

# ==============================================================================
# 8. ENTRY POINT
# ==============================================================================
if __name__ == '__main__':
    with app.app_context():
        db.create_all()
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)
