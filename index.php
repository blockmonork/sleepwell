<?php
/*
 * player fafm
 * 
 * vs 2021.11.01 - goto time option now affects youtube iframe
 * 
 * vs 2021.10.09 - added go to time option
 * 
 * vs#5 - playlist and other improvements
 * 
 * vs#4 - add playlist tag -> [playlist]\n url totalVideoDurationInSeconds \n url2 ...
 * 
 * vs#3 - add get/set positions to iframes
 * 
 * vs#2 - add support for iframes
 * 
 * vs#1 - ?
 * 
 * vs#0 - release
 * 
 * */
 
define ('_VS', '_2021.11.01');
define ('ME', 'Sleep-Well'._VS);

require('_lib.php');
$_A = new db_fake();

$PMS = ini_get('post_max_size');
$UMS = ini_get('upload_max_filesize');

$ArquivosValidos = array
	(
	'file'  => array('txt'),
	'audio' => array('mp3'),
	'video' => array('mkv', 'mp4', 'webm'),
	);
	
	
$opts = array
	(
	'ver'	=> 'Ver arquivos',
	'envF'	=> 'Enviar arquivo',
	);
	
	



//gerando lista de arquivos validos pra javascript analisar
$js_arquivos_validos = '';
foreach ( $ArquivosValidos as $tp => $A ){
	for ( $i = 0; $i < count($A); $i++ ){
		$js_arquivos_validos .= '"'.$A[$i].'",';
	}
}
// flag pra js
$js_player = false;
?>
<!doctype html>
<html>
<head>
	<meta charset='utf-8'>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="css.css?<?php echo time();?>" media="all" />
	<title><?=ME?></title>
</head>
<body>
	<div id="blekC" style="display:none;">
		<div id="bb">fdsfdasfasdfsdafadsfdsa
			<div id="bbb" onclick="blekTela()">&times;</div>
		</div>
	</div>
	<div id="body">
		<h1><?=ME?></h1>
		<?php
		foreach ( $opts as $url => $texto ){
			echo '<a class="bt" href="?option='.$url.'">'.$texto.'</a>';
		}
		echo '<div style="height:10px;">&nbsp;</div>';
		
		$option = g('option');
		if ( $option ){
			switch ( $option ){
				case 'ver':
					show_files('audio');
					show_files('video');
					show_files('file');
				break;
				case 'envF':
				// enviar arquivos
					if ( $_POST ){
						if ( !@isset($_FILES['arquivo']['tmp_name']) ){
							alerta('nada enviado');
						}
						salva($_FILES['arquivo']);
					}else{
						formFile($option, $PMS, $UMS);
					}
				break;
				case 'delF':
					$fn = g('fn');
					if ( !$fn ){
						alerta('ERRO! arquivo nao informado');
					}
					$e = explode(DIR_SEP, $fn);
					$arquivo = trim($e[0]);
					$dir = trim($e[1]);
					if ( file_exists($dir . '/' . $arquivo) ){
						echo "<div class='miniBox alert'>"
							. "<p>excluir arquivo $arquivo ?<br></p>"
							. "<a href='?option=DelF&fn=$fn'>sim</a> "
							. "<a href='?option=ver'>NAO</a>"
							. "</div>";
					}else{
						alerta('erro file!exists');
					}
				break;
				case 'DelF':
					$fn = g('fn');
					if ( !$fn ){
						alerta('ERRO arquivo nao informado');
					}
					$e = explode(DIR_SEP, $fn);
					$arquivo = trim($e[0]);
					$dir = trim($e[1]);
					if ( !file_exists($dir . '/' . $arquivo) ){
						alerta('erro file!exists');
					}
					$_A->remove($arquivo);
					unlink($dir . '/' . $arquivo);
					alerta('arquivo removido');
				break;
				case 'edtF':
					if ( $_POST ){
						$dir = p('dir');
						if ( !$dir )alerta('erro param dir!exists');
						$arquivo = p('arquivo');
						if ( !$arquivo )alerta('erro param arquivo!exists');
						$last_pos = p('lastpos');
						$x = ( !$last_pos ) ? 0 : timeStringToSeconds($last_pos);
						$_A->add($arquivo, $x)->salva();
						redir(); //alerta("atualizado");
					}else{
						$fn = g('fn');
						if ( !$fn ){
							alerta('erro arquivo nao informado');
						}
						$e = explode(DIR_SEP, $fn);
						$arquivo = trim($e[0]);
						$dir = trim($e[1]);
						if ( !file_exists($dir . '/' . $arquivo) ){
							alerta('erro file!exists');
						}						
						formEdit($option, $dir, $arquivo);
					}
				break;
				case 'play':
					$fn = g('fn');
					if ( !$fn ){
						alerta('erro arquivo nao informado');
					}
					$e = explode(DIR_SEP, $fn);
					$arquivo = trim($e[0]);
					$dir = trim($e[1]);
					if ( !file_exists($dir . '/' . $arquivo) ){
						alerta('erro file!exists');
					}
					$js_player = true;
					player($dir, $arquivo);
				break;
				case 'savePosition':
					// called by js thePlayer when sleep done
					$fn = g('fn');
					if ( !$fn ){
						alerta('erro arquivo nao informado');
					}
					$e = explode(DIR_SEP, $fn);
					$arquivo = trim($e[0]);
					$dir = trim($e[1]);
					if ( !file_exists($dir . '/' . $arquivo) ){
						alerta('erro file!exists');
					}
					$pos = g('pos');
					$_A->add($arquivo, $pos)->salva();
					redir('?option=ver');
				break;
				default:
					echo '<p><b>!option</b></p>';
				break;
			}
		}

function soma($p){
	echo intval($p[0]) + intval($p[1]);
}function texto($p){
	echo $p[0];
}$SIS = array
	(
	'functions' => function($functionName, $arrayParams){ 
		return ( function_exists($functionName) )
			? call_user_func_array($functionName, array($arrayParams) )
			: false;
		},
	);
$SIS['functions']('texto', array('fAfMpLaYeR.v.') ).'&nbsp;'.$SIS['functions']('soma', array(3, 2) );

		?>		
	</div>
	<script>
	var ArquivosValidos = [<?php echo $js_arquivos_validos;?>];
	var DIR_SEP = <?php echo '"'.DIR_SEP.'"; '; ?>
	</script>
	<script src="funcs.js?<?php echo time()?>"></script>
	<?php
	// auto start thePlayer if player exists
	if ( $js_player ){
		echo '<script> setTimeout(function(){ thePlayer.iniciar(); }, 400); </script>';		
	}
	?>
	

</body>
</html>
