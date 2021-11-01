// utils
function in_array(_Array, _string ){
	for ( var i = 0; i < _Array.length; i++ ){
		var a = _Array[i].toString().trim();
		var b = _string.toString().trim();
		if ( a == b )return true;
	}
	return false;
}
function _g(id){ return document.getElementById(id) || false; }
function verifica_tipo_arquivo(vlr){
	var a = vlr.toString();
	for ( var i = 0; i < ArquivosValidos.length; i++ ){
		var b = ArquivosValidos[i].toString();
		if ( a.match(b) ){
			return true;
		}
	}
	_g('arquivo').value = '';
	alert('tipo de arquivo nao suportado');
	return false;
}
var thePlayer = {
	id : 'player',
	last_pos : 0,
	tipo : '',
	src : '',
	w : 320,
	h : 240,
	O : null,
	delay_time : 0,
	c : 0,
	i : 0,
	is_debug : true,
	is_iframe : false,
	
	iniciar : function(){
		this.is_iframe = IS_IFRAME;
		this.O = _g(this.id);
		if ( typeof this.O == undefined || !this.O){
			alert('erro interno thePlayer !object');
			return false;
		}
		this.tipo = this._gp('data-tipo');
		this.last_pos = parseInt(this._gp('data-last-pos'))||0;
		this.src = this._gp('data-arquivo');
		if ( typeof this.O.currentTime == undefined || !this.O.currentTime ){
			this.O.currentTime = 0;
		}
		if ( this.last_pos != 0 ){
			this.O.currentTime = this.last_pos||0;
		}
		if (this.is_debug)this.debug();
		
		return this;
	},
	_gp : function(propName){
		var e = this.O||false;
		if (!e)return false;
		var v = this.O.getAttribute(propName);
		return ( typeof v == null || typeof v == undefined ) ? 0 : v;
	},
	debug : function(){
		_g('debug').innerHTML = "thePlayer.debug = tipo: " + this.tipo + ', src=' + this.src + ', last_pos:'+this.last_pos + ', this.curtime:' + this.O.currentTime;
	},
	_timeToSecs: function(strTime){
		var e = strTime.split(':');
		var h = parseInt(e[0]);
		var m = parseInt(e[1]);
		var s = parseInt(e[2]);
		if ( h != 0 ){
			h = h * 3600;
		}
		if ( m != 0 ){
			m = m * 60;
		}
		return h + m + s;
	},
	goToTime: function(){
		var t = _g('gototime').value;
		if ( t.length == 8 ){
			if ( IS_IFRAME ){
				const ifr = document.querySelector('iframe');
				ifr.src = ifr.src + '?start='+this._timeToSecs(t)
			}else{
				this.O.currentTime = this._timeToSecs(t);
			}
		}
	},
	setDelayTime : function(){
		var x = _g('sleepTime').value;
		if ( x.length == 8 ){
			this.set_delay_time(x);
		}
	},
	set_delay_time : function(strTime){
		clearInterval(C);
		this.delay_time = this._timeToSecs(strTime);
		this.i = 0;
		startCounter(this.i, this.delay_time, this.is_debug);
	},
	savePosition : function(contador){
		var t = ( IS_IFRAME ) ? (this.last_pos+parseInt(contador)) : parseInt(this.O.currentTime);
		var u = 'index.php?option=savePosition&pos='+t+'&fn='+this.src+DIR_SEP+this.tipo;
		window.location=u;
	},
	playPause : function(){
		this.O.play();
	},
};

var C = 0;
function startCounter(i, tl, debugging){
	C = setInterval(function(){
		i++;
		if ( i == tl ){
			thePlayer.savePosition(i);
		}
		var x = i.toString();
		var y = tl.toString();
		if (debugging)thePlayer.debug();
		_g('show_delay_time').innerHTML = x + ' de ' + y;
	}, 1000);
}
function blekTela(){
	_g('blekC').style.display = (_g('blekC').style.display=='none')?'block':'none';
}

// vs4 - playlist tag only
function _ga(a){
	return document.getElementById('player').getAttribute(a);
}
var PlaylistCounter = -1;
var plStart = 'start playlist counter';
var plPause = 'pause playlist counter';
var PL;

function startPlaylistCounter(){
	var txt = _g('btPl').innerText;
	if ( txt == plStart ){
		_g('btPl').innerText = plPause;
	}else{
		_g('btPl').innerText = plStart;
		clearInterval(PL);
		return;
	}
	PL = setInterval(function(){
		PlaylistCounter++;
		if ( PlaylistCounter == PLAYLIST_DURATION ){
			var da = _ga('data-arquivo');
			var dt = _ga('data-tipo');
			var nv = parseInt(_ga('data-last-pos')) +1;
			var u = 'index.php?option=play&fn=' + da + '-' + dt + '&nextVideo=' + nv;
			window.location=u;
		}
		_g('playlistShowTime').innerHTML = PlaylistCounter + ' de ' + PLAYLIST_DURATION;	
	}, 1000);
}
function playlistRedir(vlr){
	var da = _ga('data-arquivo');
	var dt = _ga('data-tipo');
	var u = 'index.php?option=play&fn=' + da + '-' + dt + '&nextVideo=' + vlr;
	window.location=u;	
}
if ( IS_PLAYLIST ){
	document.getElementById('playlistCounter').style.display='block';
	var sels = '';
	for ( let i = 0; i < PLAYLIST_TOTAL_VIDS; i++ ){
		let x = i+1;
		sels += '<option value="'+x+'">video '+x+'</option>';
	}
	var d = document.createElement('span');
	d.innerHTML = '<select onchange="playlistRedir(this.value)"><option value="">[videos]</option>'
		+ sels + '</option>';
	document.getElementById('playlistCounter').append(d);
}
