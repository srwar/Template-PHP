<? require_once("../../config.php");?>
<body > 
<?
echo $html_header;
?> 
<form action="importar_telesalud.php" method="post" enctype="multipart/form-data"> 
<table width="80%"    class="bordes" cellspacing=0 border=1 bordercolor=#E0E0E0 align="center" bgcolor='<?=$bgcolor_out?>' class="bordes">
<table width="100%" align="center" class="bordes">
  
   <tr id="mo" align="center">
    <td colspan="2" align="center">
    	<font size=+1><b>Subir archivo TELESAUD al servidor</b></font> 
	</td>
   </tr>

<tr align="center">	
	<td>	
			<h3 align="center"> Ayuda: seleccione el archivo con extencion .CSV que desea subir al servidor y luego presione el boton Enviar.</h4>
			<input type='file' name='file'> 
			<input type="submit" value="Enviar">
	</td>
	
		
		
</tr>

</table>
</table>
    <center>  
	<!--<img align="center" src="../../imagenes/logosip.gif">-->
    </center>
</form> 
</body>