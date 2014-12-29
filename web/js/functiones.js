$(document).on('ready', function(){

  $('.btn-delete').on('click', function(){

    pregunta = confirm("¿Estás seguro de eliminar?","");

    if(pregunta!=true){
      event.preventDefault();
    }

  });
});
