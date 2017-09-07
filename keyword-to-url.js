jQuery(document).ready( function($) {

	//---------------------------------------------------------------------
	//Global constants (used as defines)

	var mcnkeywordtourlSource = {
		MANUAL: 0,
		IMPORT: 1
	};
	
	//---------------------------------------------------------------------
	//Initial CSS changes
	
	mcnkeywordtourlSetCSS();
	
	//---------------------------------------------------------------------
	//MANUAL -- add a keyword-URL pair to the table from the textboxes on the page.
	
	mcnkeywordtourlTurnOffAllValidationMessages();
		
	$("#mcnkeywordtourlAdd").click( function() {

		mcnkeywordtourlTurnOffAllValidationMessages();

		insertKeywordUrlIntoTable(
			mcnkeywordtourlSource.MANUAL, $("#mcnkeywordtourlKeyword").val(), $("#mcnkeywordtourlURL").val()
		);
	});

	//Remove a row in the table when its Remove link is clicked.
	//I had to use this way since the regular way, $(".linkRemove").click(function(), did not work.
	//This is because I am assigning events to a class rather than an id.
	$(document).on( 'click', "span.mcnkeywordtourlRemoveClass", function() {
		mcnkeywordtourlTurnOffAllValidationMessages();
		$(this).parents('tr').first().remove();
		mcnkeywordtourlSaveHiddenInput();
	});

	//---------------------------------------------------------------------
	//IMPORT -- add keyword-URL pairs to the table from a file import.
	
	$("#mcnkeywordtourlImportFileChooser").click( function() {
		mcnkeywordtourlTurnOffAllValidationMessages();
	});
	
	$("#mcnkeywordtourlImportLoad").click( function() {
		
		mcnkeywordtourlTurnOffAllValidationMessages();
		
		//Check for the HTML 5 File API support.
		if ( !window.File || !window.FileReader || !window.FileList || !window.Blob ) {
			$("#mcnkeywordtourlValid5").show();
			return;
		}
		
		var fileExtension = $("#mcnkeywordtourlImportFileChooser").val().substr(-4);
		if ( fileExtension !== ".csv" && fileExtension !== ".txt" ) {
			$("#mcnkeywordtourlValid4").show();
			return;
		}		
		
		var file = $("#mcnkeywordtourlImportFileChooser")[0].files[0];
		
		if (file) {
			
			if ( !$("#mcnkeywordtourlImportAppend").prop('checked') ) {
				$("#mcnkeywordtourlTable tbody").remove();
			}
			
			var reader = new FileReader();
			reader.onload = function() {
				//Replace the linefeeds so we can read UNIX and MS-DOS file types
				var temp = this.result.replace("\r\n", "\n");
				var keywordurlpairAll = temp.split("\n");
				keywordurlpairAll.sort();
				keywordurlpairAll.forEach( function( value, index, ar ) {
					var keywordurlpairSingle = value.split(",");
					insertKeywordUrlIntoTable(
						mcnkeywordtourlSource.IMPORT, keywordurlpairSingle[0], keywordurlpairSingle[1]
					);
				});				
			}
			reader.readAsText(file);
		} else {
			$("#mcnkeywordtourlValid4").show();
		}
	});
	
	//---------------------------------------------------------------------
	//Export

	$("#mcnkeywordtourlExport").click( function() {
	
		mcnkeywordtourlTurnOffAllValidationMessages();

	});

	//---------------------------------------------------------------------
	//Shared functions
	
	function insertKeywordUrlIntoTable( source, keyword, url ) {
		
		//Trim Keywords and URL
		keyword = $.trim(keyword);
		url = $.trim(url);
		
		//Remove prefix (we calculate and add a prefix during renderings)
		url = url.replace( "http://", "" );
		url = url.replace( "mailto:", "" );
		
		//See if either field is empty	
		if ( ( keyword.length == 0 ) || ( url.length == 0 ) ) {
			if ( source == mcnkeywordtourlSource.MANUAL ) {
				$("#mcnkeywordtourlValid1a").show();
			}
			return;
		}

		//See if keyword or url contains illegal characters: quotes	
		if ( 
			( keyword.indexOf("'") != -1 ) || ( keyword.indexOf("\"") != -1 ) ||
			( url.indexOf("'") != -1 ) || ( url.indexOf("\"") != -1 )
		) {
			( source == mcnkeywordtourlSource.MANUAL ) ?
				$("#mcnkeywordtourlValid2a").show() : $("#mcnkeywordtourlValid2b").show();
			return;
		}
		
		//See if keyword contains illegal characters: ampersands
		//This is because we won't know it the user used &, &amp; or &#038;
		if ( keyword.indexOf("&") != -1 ) {
			( source == mcnkeywordtourlSource.MANUAL ) ?
				$("#mcnkeywordtourlValid2c").show() : $("#mcnkeywordtourlValid2b").show();
			return;
		}

		//See if keyword is already present in table		
		var flag = false;
		$("#mcnkeywordtourlTable").find("tr").each( function () {
			var td1 = $(this).find("td:eq(1)").text();
			if ( td1.toLowerCase() === keyword.toLowerCase() ) {
				flag = true;
				return false; //this is the way you do a break on these loops (you return false to the callback)
			}
		});
		if ( flag ) { 
			( source == mcnkeywordtourlSource.MANUAL ) ?
				$("#mcnkeywordtourlValid3a").show() : $("#mcnkeywordtourlValid2b").show();
			return;
		}
		
		var urlPrefix = ( url.indexOf('@') > 0 ) ? 'mailto:' : 'http://';
		$('#mcnkeywordtourlTable').prepend(
			'<tr>' + 
				'<td><span class="mcnkeywordtourlRemoveClass">Remove</span></td>' +
				'<td class="mcnkeywordtourlKeywordClass">' + keyword + '</td>' +
				'<td><a href="' + urlPrefix + url + '">' + url + '</a></td>' +
			'</tr>\n'
		);
		
		//We have to update the CSS here otherwise the newly inserted
		//row elements don't get the original CSS for their class.
		mcnkeywordtourlSetCSS();
		
		mcnkeywordtourlSaveHiddenInput();
	}

	function mcnkeywordtourlSaveHiddenInput() {
		
		$("#mcn_keyword_to_url_serialized").val("");
		$("#mcnkeywordtourlTable").find("tr").each( function () {
			var $tds = $(this).find('td');
			$("#mcn_keyword_to_url_serialized").val(
				$("#mcn_keyword_to_url_serialized").val() +
				$tds.eq(1).text() + "mcn0" +
				$tds.eq(2).text() + "mcn1"
			);
		});
	}
	
	function mcnkeywordtourlTurnOffAllValidationMessages() {
		
		$("#mcnkeywordtourlValid1a").hide();
		$("#mcnkeywordtourlValid2a").hide();
		$("#mcnkeywordtourlValid3a").hide();
		$("#mcnkeywordtourlValid2b").hide();
		$("#mcnkeywordtourlValid2c").hide();
		$("#mcnkeywordtourlValid4").hide();
		$("#mcnkeywordtourlValid5").hide();
	}
	
	function mcnkeywordtourlSetCSS() {
	
		//We have a hidden anchor tag on the page. We do that so we can get some
		//of its CSS properties and then set our span links to match whatever WordPress
		//styles regular anchor tag (links) use on that page.
		$("#mcnkeywordtourlBaseAnchor").css( "display", "none" );
		
		$(".mcnkeywordtourlRemoveClass").css( "color", $("#mcnkeywordtourlBaseAnchor").css("color") ); 
		$(".mcnkeywordtourlRemoveClass").css( "textDecoration", $("#mcnkeywordtourlBaseAnchor").css("textDecoration") ); 
		$(".mcnkeywordtourlRemoveClass").css( "cursor", $("#mcnkeywordtourlBaseAnchor").css("cursor") ); 
		
		$(".mcnkeywordtourlKeywordClass").css( "padding-left", '8px' ); 
		$(".mcnkeywordtourlKeywordClass").css( "padding-right", '8px' ); 
		
		//We want to hide the message that says the setting were saved because it could persist
		//even after the user makes changes (Remove) to the table. We want to keep this message area
		//available for other errors that WordPress may report so We just hide it if it contains
		//the 'Settings saved.' message
		$("#setting-error-settings_updated:contains('Settings saved.')").css( "display", "none" );
	}
});