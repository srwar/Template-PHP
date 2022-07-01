<?php

require_once ("../../config.php");

ini_set('post_max_size','1G');
ini_set('upload_max_filesize','500M');
ini_set('max_execution_time','12000');
ini_set('max_input_time','60');
$usuario=$_ses_user['id']; 
$tipo = $_FILES['file']['type']; 
$tamanio = $_FILES['file']['size']; 
$archivotmp = $_FILES['file']['tmp_name']; 
$nombre=$_FILES['file']['name'];


$sql="select cuie from sistema.usuarios where id_usuario ='$usuario'";
$res_sql=sql($sql, "Error") or fin_pagina();
$cuie=$res_sql->fields['cuie'];
$contenedora= trim($cuie."\ ");
	
	//Si no existe la carpeta repositorio para ese cuie, la crea
	if(!file_exists(trim("C:\ArchivosParaTele\ ").$cuie)){
		mkdir(trim("C:\ArchivosParaTele\ ").$cuie, 0777);
	}
	
	if(move_uploaded_file($archivotmp,trim("C:\ArchivosParaTele\ ").$contenedora."TELESALUD.csv") ){
		Echo("El archivo se subio correctamente");
	} else {
		Echo("El archivo no se pudo subir al servidor, inténtalo mas tarde");
		}

?> 
 
  

