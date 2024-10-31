function TinyMCE_rummovie_getControlHTML(control_name) {
	switch (control_name) {
		case "rummovie":
			var buttons = '<a href="javascript:tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mceRumMovie\')" target="_self" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mceRumMovie\');return false;"><img id="{$editor_id}_help" src="{$pluginurl}/images/imdb.gif" title="IMDB" width="20" height="20" class="mceButtonNormal" onmouseover="tinyMCE.switchClass(this,\'mceButtonOver\');" onmouseout="tinyMCE.restoreClass(this);" onmousedown="tinyMCE.restoreAndSwitchClass(this,\'mceButtonDown\');" /></a>';

			return buttons;
	}

	return "";
}

function TinyMCE_rummovie_execCommand(editor_id, element, command, user_interface, value) {

	// Handle commands
	switch (command) {
		case "mceRumMovie":
			OpenMovieSearch();
			/*
			var template = new Array();

			template['file']   = 'rummovie.php';
			template['width']  = 480;
			template['height'] = 380;

			args = {
				resizable : 'yes',
				scrollbars : 'yes'
			};

			tinyMCE.openWindow(template, args);
			*/
			return true;
	}

	// Pass to next handler in chain
	return false;
}
