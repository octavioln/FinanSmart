window.addEventListener('show-alert', event =>{

    Swal.fire({
        icon: 'warning',
        title: 'Confirmar',
        text: '¿Confirmas eliminar el registro?',
        type: 'warning',
        showCancelButton: true,
        cancelButtonText: 'Cerrar',
        cancelButtonColor: '#dc2626',
        confirmButtonColor: '#166534',
        confirmButtonText: 'Aceptar'

    }).then(function(result){
        if(result.value){
            Livewire.emit('deleteConfirmed')
        }
    })
})
window.addEventListener('post-deleted', function(event) {
    const message = event.detail;
    Swal.fire({
        icon: 'success',
        text: message,
        timer: 2000,
        showCancelButton: false,
        showConfirmButton: false
    })
});
