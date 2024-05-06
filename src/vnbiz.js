import "https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.3.0/jquery.form.min.js";

$(function () {
	let closeModal = function () {
		if (window.frameElement) {
			console.log(window.frameElement.id, "iframeid");
			window.parent.jQuery("#" + window.frameElement.id).remove();
		}
	}
	$("form.ajax").on("submit", function (e) {
	    e.preventDefault(); // prevent native submit
	    var $this = $(this);
	    if ($this.find(".errors").length) {
	    	$this.find(".errors").html();
	    } else {
    		$this.append("<div class='errors'></div>");
	    }
	    $this.ajaxSubmit({
	    	success: function () {
	    		if ($this.attr("goto")) {
	    			window.location.href = $this.attr("goto")
	    			return;
	    		}
	    		if (typeof $this.attr("close-modal") != 'undefined') {
		    		if (typeof $this.attr("reload") != 'undefined') {
		    			window.parent.location.reload();
		    			return;
		    		}
	    			closeModal();
	    			return;
	    		}
	    		location.reload();
	    	},
	    	error: function (e) {
	    		var msg = e.statusText;
	    		if (e.responseJSON && e.responseJSON.error) {
	    			msg = e.responseJSON.error;
	    		}
	    		$this.find('.errors').html(msg);
	    	}
	    })
	});
	
	$("[window]").on("click", function (e) {
		let $this = $(this);
		
		let newId = ("M" + Math.random()).replace(".", "_");
		$("body").append("<div id='" + newId + "' class='window'><iframe class='window-body' src='" + $this.attr('window') + "'></iframe></div>");
		
		
		let closeFn = function () {
			$('body').css("overflow", "inherit");
			$('body').off('click', closeFn); 
			$("#" + newId).remove();
		};

		window.setTimeout(function() {
			$('body').css("overflow", "hidden");
			$('body').on('click', closeFn);
			// let iframe = document.querySelector("#" + newId);
			
			// iframe.addEventListener('load', function() {
			// 	iframe.style.height =( iframe.contentDocument.body.scrollHeight + 15)+ 'px';
			// 	iframe.style.width = iframe.contentDocument.body.scrollWidth + 'px';
			// });	
		}, 0);
	});
});