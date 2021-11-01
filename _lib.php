<?php
header('Content-Type:text/html; charset=utf-8;');

define ('DIR_AUDIO', 'audios');
define ('DIR_VIDEO', 'videos');
define ('DIR_FILE', 'files');
define ('DIR_SEP', '-');


if (!is_dir(DIR_AUDIO))mkdir(DIR_AUDIO)or die('erro ao criar dir audio');
if (!is_dir(DIR_VIDEO))mkdir(DIR_VIDEO)or die('erro ao criar dir video');
if (!is_dir(DIR_FILE))mkdir(DIR_FILE)or die('erro ao criar dir file');

class db_fake{
	private $_arq = 'configs.txt';
	// line format: fileName | lastPosition (NL)
	private $_sepL = "\n";
	private $_sepC = "|";
	private $_linhas = '';
	private $_fp = null;
	private $_conteudo = array(); // filename => lastPosition
	
	public function __construct(){
		if ( !@file_exists($this->_arq) ){
			$this->_abrir();
			$this->_fechar();
		}else{
			$this->_populate();
		}
	}
	private function _abrir($mode="w"){
		$this->_fp = fopen($this->_arq, $mode) or die('erro ao criar arquivo configs.txt');
	}
	private function _fechar(){
		fclose($this->_fp);
	}
	private function _escreve(){
		$this->_abrir();
		fputs($this->_fp, $this->_linhas);
		$this->_fechar();
	}
	private function _e($A, $i){
		return ( @isset($A[$i]) ) ? trim($A[$i]) : '';
	}
	private function _populate(){
		$fgc = file_get_contents($this->_arq);
		if ( strlen($fgc) == 0 )return;
		$E = explode($this->_sepL, $fgc);
		for ( $i = 0; $i < count($E); $i++ ){
			if ( !empty(trim($E[$i])) ){
				$L = explode($this->_sepC, trim($E[$i]));
				$this->_conteudo[ $this->_e($L, 0) ] = $this->_e($L, 1);
			}// fi !empty Ei
		}
	}
	
	public function get($fileName=''){
		if ( $fileName == '' ){
			return $this->_conteudo;
		}else{
			return ( array_key_exists($fileName, $this->_conteudo) )
				? array($this->_conteudo[$fileName] )
				: array();
		}
	}
	public function existe($fileName){
		return ( array_key_exists($fileName, $this->_conteudo) )?true:false;
	}
	public function add($fileName, $position=0){
		$this->_conteudo[$fileName] = $position;
		return $this;
	}
	public function remove($fileName){
		$novo = array();
		foreach ( $this->_conteudo as $fn => $pos ){
			if ( trim($fn) != trim($fileName) ){
				$novo[$fn] = $pos;
			}
		}
		$this->_conteudo = array();
		foreach ( $novo as $fn => $pos ){
			$this->_conteudo[$fn] = $pos;
		}
		$this->salva();
		return $this;
	}
	public function salva(){
		$this->_linhas = '';
		foreach ( $this->_conteudo as $fn => $pos ){
			$this->_linhas .= trim($fn) . $this->_sepC . trim($pos) . $this->_sepL;
		}
		$this->_escreve();
	}
	
	
};//END class



// utils
function g($var){ return ( @isset($_GET[$var]) ) ? trim($_GET[$var]) : false; }
function p($var){ return ( @isset($_POST[$var]) ) ? trim($_POST[$var]) : false; }
function alerta($msg, $pg='index.php'){ echo '<script>alert("'.$msg.'");</script>'; redir($pg); }
function redir($pg='index.php'){echo '<script>window.location="'.$pg.'"; </script>'; exit; }
function has_i($A, $i, $ret=false){return ( @isset($A[$i]) ) ? trim($A[$i]) : $ret;}

function get_ext($file){
	$x = explode('.', $file);
	$r = ( count($x)==0 ) ? false : $x[count($x)-1];
	return $r;	
}
function is_what($file){
	global $ArquivosValidos;
	$r = get_ext($file);
	if ( !$r )return false;
	if ( in_array($r, $ArquivosValidos['audio']) ){
		return 'audio';
	}else if ( in_array($r, $ArquivosValidos['video']) ){
		return 'video';
	}else if( in_array($r, $ArquivosValidos['file'])){
		return 'file';
	}else{
		return false;
	}
}
function get_dir_by_filetype($ft){
	switch ( $ft ){
		case 'audio':
		return DIR_AUDIO;
		break; 
		case 'video':
		return DIR_VIDEO;
		break;
		case 'file':
		return DIR_FILE;
		break;
		default:
		return '';
		break;
	}
}
function ler($dir){
	$od = opendir($dir);
	if ( !$od )die('impossivel ler '.$dir);
	$A = array();
	while ( false !== ( $r = readdir($od) ) ){
		if ( $r != '.' && $r != '..' ){
			array_push($A, $r);
		}
	}
	if (count($A) > 0)sort($A);
	return $A;
}
function salva($arq){
	global $_A;
	if ( !@isset($arq['name']) )die('!file');
	$tp = is_what($arq['name']);
	if ( !$tp )die('tipo nao permitido');
	$d = get_dir_by_filetype($tp);
	$fn = preg_replace('/\W/i', '.', strtolower($arq['name']) );
	$destino = $d . '/' . $fn;
	if ( !is_uploaded_file($arq['tmp_name']) )die('!uploaded');
	if ( !move_uploaded_file($arq['tmp_name'], $destino) )die('!moved');
	$_A->add($fn)->salva();
	redir();
}
function pz($vlr){
	$x = intval($vlr);
	return ( $x < 10 ) ? "0".$x : $x;
}
function minutar($segundos, $returnAsString=true){
	$_seg = intval($segundos);
	$h = $m = $s = 0;
	if ( $_seg < 0 )return false;
	while ( $_seg > 0 ){
		$s++;
		if ( $s == 60 ){
			$s = 0;
			$m++;
		}
		if ( $m == 60 ){
			$m = 0;
			$h++;
		}
		$_seg -=1;
	}
	return ( $returnAsString )
		? pz($h) .":".pz($m).":".pz($s)
		: array( pz($h), pz($m), pz($s) );
}
function timeStringToSeconds($tempo){
	$e = explode(':', $tempo);
	$h = has_i($e, 0, 0);
	$m = has_i($e, 1, 0);
	$s = has_i($e, 2, 0);
	return intval($h)*3600 + intval($m)*60 + intval($s);
}


// ---- forms
function formFile($option, $max_post, $max_upload){
$F = <<< EOF
<div class="miniBox">
Enviar arquivo
<hr>
<form enctype="multipart/form-data" method="post" action="?option={$option}">
<input type='hidden' name='posted' value='1'>
<p>
<label for="arquivo"><i>se for embed iframe playlist, setar:<br>[playlist] 
<br> videoURL videoTotalDurationInSeconds 
<br> video2 ...so on... que sistema gera o iframe sozinho.</i></label><br>
<input style='width:50%; border:1px solid silver; text-align:center; padding:5px; background:#efefef;' 
	required type='file' name='arquivo' id='arquivo' onchange='verifica_tipo_arquivo(this.value)'>
</p>
<input type='submit'>
</form>
<hr>
<p><i><small>limites post: $max_post e upload: $max_upload</small></i></p>
</div>
EOF;
echo $F;
}

function formEdit($option, $dir, $arquivo){
	global $_A;
	$last_pos = ( $_A->existe($arquivo) ) ? $_A->get($arquivo)[0] : 0;
	$x = ( $last_pos != 0 ) ? minutar($last_pos) : '00:00:00';
// show seconds in google chrome
// https://stackoverflow.com/questions/14487621/how-do-i-force-seconds-to-appear-on-an-html5-time-input-control/14487701
	
$F = <<< EOF
<div class='miniBox'>
editar arquivo <b>$arquivo </b><hr>
<form action='?option=$option' method='post'>
<input type='hidden' name='arquivo' value='$arquivo'>
<input type='hidden' name='dir' value='$dir'>
<button onclick='javascript:history.back();'>&larr;</button>
&nbsp;
last position:
<input type='time' name='lastpos' id='lastpost' value='$x' step='1'>
&nbsp;
<input type='submit' value='salvar'>
</form>
</div>
EOF;
echo $F;
}

// --- tela
function show_files($what){
	global $_A;
	$D = get_dir_by_filetype($what);
	$item = ler($D);
	$tl = count($item);
	if ( $tl == 0 ){
		echo "<p>nenhum arquivo de $what</p>";
	}else{
		echo "<p>$tl arquivo(s) de $what</p>";
		for ( $i = 0; $i < $tl; $i++ ){
			$last_pos = 0;
			$fileName = $item[$i];
			$fn =  $fileName . DIR_SEP . $D;
			if ( $_A->existe($item[$i]) ){
				$last_pos = $_A->get($item[$i])[0];
				if($last_pos!=0)$last_pos = minutar($last_pos);
			}
			$o1 = '?option=edtF&fn='.$fn;
			$o2 = '?option=delF&fn='.$fn;
			$o3 = '?option=play&fn='.$fn;
			echo "<div class='miniBox'><span>$fileName - last position: $last_pos </span>"
				. "<br>"
				. "<a href='$o1'>editar info</a>"
				. "<a href='$o2'>remover arquivo</a>"
				. "<a href='$o3'>PLAY</a>"
				. "</div>";
		}
	}
	echo "<hr><p></p>";	
}

function player($dir, $arquivo){
	global $_A;
	$last_pos = ( $_A->existe($arquivo) ) ? $_A->get($arquivo)[0] : 0;
	$src = $dir . '/' . $arquivo;

	$sleep = '<p>
	<input type="time" id="sleepTime" step="1" value="00:00:00"> 
		<a href="javascript:thePlayer.setDelayTime()">set sleep time</a>
	</p>
	<p>
	<input type="time" id="gototime" step="1" value="00:00:00">
		<a href="javascript:thePlayer.goToTime()">go to</a>
	</p>
	<p><a href="javascript:blekTela()">blekTela</a></p>
	<p id="show_delay_time"></p>
	<p id="debug"></p>
	';
	
	// new: js var IS_IFRAME[bool] for yt embedded media, so js struct inject time into its src string
	$is_iframe = "false;";
	// new: playlist tag
	$is_playlist = "false;";
	$playlist_duration = "0;";
	$pl_total_vids = "0;";

	if ( $dir == DIR_AUDIO ){
		//https://www.w3schools.com/tags/tag_audio.asp
		$F = '<audio 
				controls 
				loop 
				id="player" 
				data-arquivo="'.$arquivo.'"
				data-tipo="'.$dir.'" 
				data-last-pos="'.$last_pos.'">
			<source src="'.$src.'" type="audio/mpeg">
			Your browser does not support the audio tag.
			</audio>';
	}else if ($dir == DIR_FILE ){
		$is_iframe = "true;";
		$F = file_get_contents($dir.'/'.$arquivo);
		
		if ( preg_match('/YouTube/i', get_string_between($F, 'title="', '"'))){
			$F = yt_iframe($F, $arquivo, $dir, $last_pos);

		}else if ( $F[0] == '[' ){
			$is_playlist = "true;";
			$Vids = explode("\n", $F);
			$vid1 = '';
			$nextVideo = g('nextVideo');
			$nv = ( $nextVideo ) ? intval($nextVideo) : 1;
			if ( $nv >= count($Vids) ){
				goto cabou;
			}
			// avoid empty lines & passing total videos to js
			$A = [];
			for ( $v = 1; $v < count($Vids); $v++ ){
				if ( !empty(trim($Vids[$v]))){
					array_push($A, $Vids[$v]);
				}
			}
			$pl_total_vids = count($A).';';
			for ( $i = 0; $i < count($A); $i++ ){
				$e = explode(' ', trim($A[$i]));
				//var_dump($e); 
				$u = trim($e[0]);
				$s = trim($e[1]);
				//var_dump("u: $u   s: $s"); exit;
				if ( $i == ($nv-1) ){
					$vid1 = $u;
					$playlist_duration = "$s ;";
				}
			}
			if ( $vid1 != '' ){
				$F = yt_iframe($vid1, $arquivo, $dir, $nv, true);
			}else{
				cabou:
				echo '<script>window.location="index.php"</script>';
				exit;
			}
			
		}else{
			$F = str_replace('<iframe ', '<iframe id="player" 
			data-arquivo="'.$arquivo.'" 
			data-tipo="'.$dir.'" 
			data-last-pos="'.$last_pos.'" ', $F);
		}
	}else{
		$e = get_ext($arquivo);
		
		//https://www.w3schools.com/tags/tag_video.asp
		// mkv format:
		//https://stackoverflow.com/questions/21192713/how-to-playback-mkv-video-in-web-browser#:~:text=5%20Answers&text=HTML5%20does%20not%20support%20.,can%20use%20this%20code...&text=But%20it%20depends%20on%20the,it%20will%20play%20or%20not.
		
		$src2 = ( $e == 'mkv' )
		? '<source src="'.$src.'" type="video/mp4">'
		:'<source src="'.$src.'" type="video/'.$e.'">';
		
		$F = '<video 
				controls 
				loop 
				id="player" 
				data-arquivo="'.$arquivo.'"
				data-tipo="'.$dir.'" 
				data-last-pos="'.$last_pos.'" 
				width="320" height="240">
			'.$src2.'
			Your browser does not support the video tag.
			</video>';
	}

echo "<script>
var IS_IFRAME = $is_iframe 
var IS_PLAYLIST = $is_playlist 
var PLAYLIST_DURATION = $playlist_duration 
var PLAYLIST_TOTAL_VIDS = $pl_total_vids 
</script>
<div class='miniBox' id='playerContainer'>$F $sleep</div>
<p id='playlistCounter' style='display:none'>
<button id='btPl' onclick='startPlaylistCounter()'>start playlist counter</button>
</p>
<div id='playlistShowTime'></div>
";	

}

function get_string_between($string, $a, $b){
	$temp = trim($string);
	$pos1 = strpos($temp, $a) + strlen($a);
	$pos2 = strlen($temp);
	$ret = '';
	for ( $i = $pos1; $i < $pos2; $i++ ){
		$c = $temp[$i];
		if ( $c != $b ){
			$ret .= $c;
		}else{
			return $ret;
		}
	}
	return false;
}

function yt_iframe($src, $arquivo, $dir, $last_pos, $is_playlist=false){
	if ( !$is_playlist ){
		$w = get_string_between($src, 'width="', '"');
		$h = get_string_between($src, 'height="', '"');
		$url = get_string_between($src, 'src="', '"');
	}else{
		$w = 560;
		$h = 315;
		$url = $src;
	}
	if ( $last_pos != 0 && !$is_playlist){
		$url .= '?start='.$last_pos;
	}
	$x = '<iframe id="player" 
			data-arquivo="'.$arquivo.'" 
			data-tipo="files" 
			data-last-pos="'.$last_pos.'" width="'.$w.'" height="'.$h.'" src="'.$url.'" title="YouTube video player" frameborder="0" 
			allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
	return $x;	
}
?>
