<div wire:ignore
    x-data="{ open: false }"
    x-show="open"
    x-on:swal-toast.window="
        open = true;
        Swal.mixin({
            theme: 'auto',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        }).fire({
            icon: event.detail.icon,
            title: event.detail.title,
            text: event.detail.text,
        });
    ">
</div>