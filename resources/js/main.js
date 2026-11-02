window.addEventListener('show-alert', event =>{

    Swal.fire({
        icon: 'warning',
        title: 'Confirmar',
        text: '¿Está seguro que desea eliminar el registro?',
        type: 'warning',
        showCancelButton: true,
        cancelButtonText: 'Cerrar',
        cancelButtonColor: '#dc2626',
        confirmButtonColor: '#166534',
        confirmButtonText: 'Aceptar'

    }).then(function(result){
        if(result.value){
            Livewire.dispatch('deleteConfirmed')
        }
    })
});
