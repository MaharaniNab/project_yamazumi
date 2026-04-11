<div class="text-center">
    <img src="data:image/png;base64,{{ $chartBase64 }}" alt="Grafik Yamazumi" class="img-fluid border shadow">
</div>

<h3>Rekomendasi Kaizen</h3>
<ul>
    @foreach($hasilAlgoritma['rekomendasi_list'] as $rek)
        <li>{{ $rek }}</li>
    @endforeach
</ul>