<?php 

require_once("../../config.php");

function cabecera($puntero){
	list($a,$fecha_prestacion,$c,$d,$dni,$f,$g,$h,$i,$prestacion,$diagnostico,$l,$m,$n,$o,$p,$q)=$puntero;

	if (trim($fecha_prestacion)=='FECHA PRESTACION' and trim($dni)=='Numero Documento Paciente'  and trim($prestacion)=='Prestacion Sumar'  and trim($diagnostico)=='Diagnostico'){
		return 1;
	}else{
		return 0;
	}
}

function datosVacios($puntero){
	list($a,$fecha_prestacion,$c,$d,$dni,$f,$g,$h,$i,$prestacion,$diagnostico,$l,$m,$n,$o,$p,$q)=$puntero;
	
	if (trim($fecha_prestacion)=='' or trim($dni)==''  or trim($prestacion)==''  or trim($diagnostico)==''){
		return 1;
	}else{
		return 0;
	}
}

function prestacionCargada($id_smiafiliados, $fecha_prestacion, $id_nomenclador){
	$sql_prestacion="SELECT facturacion.comprobante.id_smiafiliados, 
	facturacion.prestacion.fecha_prestacion, 
	facturacion.prestacion.id_nomenclador
	FROM facturacion.comprobante
	INNER JOIN facturacion.prestacion ON (facturacion.comprobante.id_comprobante=facturacion.prestacion.id_comprobante)
	WHERE facturacion.comprobante.marca=0 
	AND facturacion.comprobante.id_smiafiliados=$id_smiafiliados
	AND facturacion.prestacion.fecha_prestacion='$fecha_prestacion'
	AND facturacion.prestacion.id_nomenclador=$id_nomenclador";
	
	$res_presta=sql($sql_prestacion, "Error") or fin_pagina();

	if($res_presta->EOF){
		return 0;
	}else{
		return 1;
	}
	
}
//FUNCION CONTIENE EL FORMATO CORRECTO PARA SER INSERTADO EN LA BBDD
function validarFecha($fecha_prestacion){
	
	$fecha_prestacion= Fecha_db(ereg_replace('-','/',$fecha_prestacion));
	
	$newDate = date("m/d/Y", strtotime($fecha_prestacion));
		
	return $newDate;
}

//CREAMOS FUNCION PARA CONVERTIR FECHAS, RESTARLAS Y OBTENER EDAD DEL BENEFICIARIO
function convertirFechaParaEdad($fecha_nacimiento){
	$fecha_nacimiento= Fecha_db(ereg_replace('-','/',$fecha_nacimiento));
	$newDate = date("Y-m-d", strtotime($fecha_nacimiento));
	
	return $newDate;
}

//CREAMOS FUNCION PARA COMPARAR NOMBRES Y APELLIDOS 
function personaDiferente($d,$afiapellido,$afinombre){
	//$arrayTele, $arraySumar
	$coincidencia=0;
	$arrayTele = explode(" ", $d);
	$afiNomApe = $afiapellido." ".$afinombre;
	$arraySumar = explode(" ", $afiNomApe);
	for($i=0;$i<count($arrayTele);$i++){
		for($j=0;$j<count($arraySumar);$j++){
			if (strcmp($arrayTele[$i], $arraySumar[$j]) !== 0){
				//no son iguales
			}else{
				$coincidencia++;
			}
		}
	}
	if($coincidencia===0){
		return 1;
	}else{
		return 0;
	}
}

if ($_POST['importarTele']){
	
	$periodo = $_POST["teleperiodo"];
	$usuario=$_ses_user['id'];
	
	$sql="select cuie from sistema.usuarios where id_usuario ='$usuario'";
	$res_sql=sql($sql, "Error") or fin_pagina();
	$cuie=$res_sql->fields['cuie'];
	$contenedora= trim($cuie."\ ");

	$archivo=trim("C:\ArchivosParaTele\ ").$contenedora."TELESALUD.csv";
	
	$fp = fopen ( $archivo , "r" );
	
	//creo nombre archivo
	$log="log_".date("d-m-Y_his");
	//asigno la ubicacion
	$archivo_log = trim("C:\ArchivosParaTele\ ").$contenedora.$log.".txt";
	//creo y abro archivo
	$fp_log = fopen($archivo_log, "a+" );
	
	$data = fgetcsv ($fp , 2048, ';','\'');
	list($a,$b,$c,$d,$e,$f,$g,$h,$i,$j,$k,$l,$m,$n,$o,$p,$q)=$data;
	$count=0;
	$edad=0;
	
	if($periodo != -1){
		if(cabecera($data)){
			$db->StartTrans();
			while((( $data = fgetcsv($fp , 2048, ';','\'')) !== false )){
			
				list($a,$fecha_prestacion,$c,$d,$dni,$f,$g,$h,$i,$prestacion,$diagnostico,$l,$m,$n,$o,$p,$q)=$data;
				
				//validar datos vacios
				$error=''; //limpio la variable error
				if(datosVacios($data)){
					$error = "Al paciente ".$d." le faltan datos: Fecha de prestacion, DNI, Prestacion Sumar y/o Diagnostico"."\r\n";
				}else{
					$sql_buscoAfil=" select id_smiafiliados,afidni,clavebeneficiario,afiapellido,afinombre,activo,afisexo,embarazo_actual,mensajebaja,afifechanac
								from nacer.smiafiliados 
								where afidni = '".$dni."' AND aficlasedoc='P' AND activo='S'";
					$resultAfil=sql($sql_buscoAfil);
					if($resultAfil->EOF){ // no se encontro con clave beneficiario y nro doc
						$error ="No se encontro afiliado ".$d." con clave beneficiario y nro documento"."\r\n";
					}else{
						$fecha_prestacion2 = validarFecha($fecha_prestacion);//MODIFICO FECHA PRESTACION
						
						//OBTENEMOS CLAVE, ID_SMI DE BENEFICIARIO Y EMBARAZO_ACTUAL
						$id_smiafiliados=$resultAfil->fields['id_smiafiliados'];
						$claveBenef=$resultAfil->fields['clavebeneficiario'];
						$fechaNac=$resultAfil->fields['afifechanac'];
						$embarazada=$resultAfil->fields['embarazo_actual'];
						$activo=$resultAfil->fields['activo'];
						$afinombre=$resultAfil->fields['afinombre'];
						$afiapellido=$resultAfil->fields['afiapellido'];
						
						//VERIFICAMOS QUE SEA LA MISMA PERSONA
						if(personaDiferente($d,$afiapellido,$afinombre)){
							$error = '<font color="red">'."La persona ".$d." DNI:" .$dni." de la planilla es diferente del beneficiario ".$afiapellido." ".$afinombre.' </font>'."\r\n";
						}else{
							$fechaNac2 = convertirFechaParaEdad($fechaNac);//MODIFICO FECHA NACIMIENTO
							$fecha_prestacion3 = convertirFechaParaEdad($fecha_prestacion);//MODIFICO FECHA Prestacion -------------
													
							//OBTENEMOS EL ID_NOMENCLADOR_DETALLE SEGUN FECHA PRESTACION
							$sql_presta="SELECT id_nomenclador_detalle FROM facturacion.nomenclador_detalle WHERE modo_facturacion='4' AND '$fecha_prestacion2' BETWEEN fecha_desde AND fecha_hasta";
							$resultPresta=sql($sql_presta);
							$id_nom_detalle= $resultPresta->fields['id_nomenclador_detalle'];
							
							//OBTENEMOS GRUPO ETAREO DEL BENEFICIARIO
							$edad = ($fecha_prestacion3-$fechaNac2);
							
							//SEGUN EL ESTADO DE EMBARAZO ACTUAL CONSULTAMOS POR EDAD O DIRECTAMENTE OBTENEMOS EL GRUPO
							if($embarazada == 'N'){
								$sql_grupoEtareo="SELECT id FROM facturacion.grupo_etareo WHERE $edad BETWEEN edad_inicio AND edad_fin";
								$resultGE=sql($sql_grupoEtareo);
								$grupoEtareo= $resultGE->fields['id'];  //ACA OBTENGO EL ID DEL GRUPO ETAREO
							}else{
								$sql_grupoEtareo="SELECT id FROM facturacion.grupo_etareo WHERE id=1";
								$resultGE=sql($sql_grupoEtareo);
								$grupoEtareo= $resultGE->fields['id'];  //ACA OBTENGO EL ID DEL GRUPO ETAREO
							}
							//OBTENEMOS ID PRESTACION
							$sql_id_presta="SELECT id_nomenclador FROM facturacion.homo_tele WHERE grupo_etareo=$grupoEtareo AND descripcion='$prestacion' AND id_detalle_nom=$id_nom_detalle";
							$result_id_presta=sql($sql_id_presta);
							$id_presta= $result_id_presta->fields['id_nomenclador'];  //ACA OBTENGO EL ID DE LA PRESTACION
							
							if($id_presta==''){
								$error ="La prestacion: ".$prestacion." no corresponde al grupo etareo del beneficiario/a: ".$d ."| Embarazo Actual:". $embarazada ."\r\n";
							}else{
								//OBTENEMOS DIAGNOSTICO DE LA PRESTACION 
								$largo = strlen($diagnostico);
								$token = '-';
								$posicionToken = strpos($diagnostico, $token);
								$diag = substr($diagnostico, $posicionToken+1, $largo);  //ACA OBTENGO EL DIAGNOSTICO
								
								//OBTENEMOS ID COMPROBANTE
								$id="select nextval('facturacion.comprobante_id_comprobante_seq') as id_comprobante";
								$id_comprobante=sql($id) or fin_pagina();
								$id_comprobante=$id_comprobante->fields['id_comprobante'];
								
								if(prestacionCargada($id_smiafiliados,$fecha_prestacion2,$id_presta)){
									$error ="La prestacion ".$prestacion." que intenta importar para el benef. ".$d." ya se encuentra cargada"."\r\n";
								}else{
									$fcarga = date("Y/m/d h:i:s"); //OBTENEMOS FECHA DE CARGA 
									
									//INSERTAMOS COMPROBANTE
									$sql_comprob="INSERT INTO facturacion.comprobante
														(id_comprobante,cuie,fecha_comprobante,clavebeneficiario,id_smiafiliados,fecha_carga,periodo,activo,alta_comp,usuario)
														VALUES
														($id_comprobante,'$cuie','$fecha_prestacion2','$claveBenef',$id_smiafiliados,'$fcarga','$periodo','$activo','','TELESALUD')";
														
									sql($sql_comprob)or die(mysql_error());
									
									//OBETENEMOS LA PRESTACION DEL NOMENCLADOR SEGUN SU ID_NOMENCLADOR
									$sql_precio="Select * from facturacion.nomenclador where id_nomenclador='".$id_presta."'";
									$resulPrecio=sql($sql_precio);
									
									$precio=$resulPrecio->fields['precio'];
									$grupo=$resulPrecio->fields['grupo'];
									$codigopres=$resulPrecio->fields['codigo'];
									$id_detalle=$resulPrecio->fields['id_nomenclador_detalle'];
																	
									//INSERTAMOS PRESTACION
									$sql_presta="INSERT INTO facturacion.prestacion
															(id_comprobante,id_nomenclador,precio_prestacion,diagnostico,usuario,fecha_prestacion,embarazada)
															VALUES
															($id_comprobante,$id_presta,$precio,'$diag','TELESALUD','$fecha_prestacion2','$embarazada')";
									sql($sql_presta)or die(mysql_error());
									
									//ECHO CON PRESTACION INSERTADA
									$msg_ok = '<font color="green">ID COMP: '. $id_comprobante ." | Se inserto la prestacion ". $prestacion . " para el beneficiario:". $d ."| Clave Beneficiario:".$claveBenef ."| DNI:".$dni.' </font>'."\r\n";
									echo $msg_ok;
									fputs($fp_log,$msg_ok); //INSERTAMOS PRESTACIONES IMPORTADAS EN LOG
								}
							}
						}						
					}
				}
				echo $error;
				fputs($fp_log,$error); //INSERTAMOS ERRORES EN LOG 
				echo "<br>";
			}
			$db->CompleteTrans();
		}else{
			echo "Error: controle la cabecera del archivo";
		}
	}else{
		echo "Error: Debe seleccionar un periodo";
	}
	
	fclose ($fp);
	fclose ($fp_log);
	
	
		
		
}
?> 
 