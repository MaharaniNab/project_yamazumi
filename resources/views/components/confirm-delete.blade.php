<script>
    window.addEventListener('confirm-delete', event => {
        const {
            message
            , eventName
        } = event.detail
        Swal.fire({
            title: 'Konfirmasi Hapus'
            , text: message ?? 'Apakah Anda yakin?'
            , icon: 'warning'
            , showCancelButton: true
            , confirmButtonColor: '#dc2626'
            , cancelButtonColor: '#2563eb'
            , confirmButtonText: 'Ya, hapus!'
            , cancelButtonText: 'Batal'
        , }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch(eventName)
            }
        });
    });

</script>
