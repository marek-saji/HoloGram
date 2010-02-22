/**
 * base code from:
 * http://swip.codylindley.com/DOMWindowDemo.html
 *
 * modified by:
 * @author m.augustynowicz
 *
 * known bugs:
 * * jeśli pojawia się jakiś, zanim zginie poprzedni -- pusty tekst.
 * * lots of @fixme tags
 */

(function($){
	
	//closeDOMWindow
	$.fn.closeDOMWindow = function(settings){
		
		if(!settings){settings={};}
		
		var run = function(passingThis){
			
			if(settings.anchoredClassName){
                /**
                 * @fixme !! "fast" instead of "1" would be nice..
                 *        but then some collisions may occur.
                 */
				$('.'+settings.anchoredClassName).fadeOut(1,function(){
                    /** @fixme !! it's b0rken. but this way it works with googlemaps.. /: */
                    var has_unload = false;
                    if($.fn.draggable){
                        if (has_unload)
						    $('.'+settings.anchoredClassName).draggable('destory').trigger("unload").remove();
                        else
                            $('.'+settings.anchoredClassName).draggable('destroy').remove();
					}else{
                        if (has_unload)
                            $('.'+settings.anchoredClassName).trigger("unload").remove();
                        else
                            $('.'+settings.anchoredClassName).remove();
					}
				});
				if(settings.functionCallOnClose){settings.functionCallAfterClose();}
			}else{
				$('#DOMWindowOverlay').fadeOut('fast',function(){
					$('#DOMWindowOverlay').trigger('unload').unbind().remove();																	  
				});
				$('#DOMWindow').fadeOut('fast',function(){
					if($.fn.draggable){
						$('#DOMWindow').draggable("destroy").trigger("unload").remove();
					}else{
						$('#DOMWindow').trigger("unload").remove();
					}
				});
			
				$(window).unbind('scroll.DOMWindow');
				$(window).unbind('resize.DOMWindow');
				
				if($.fn.openDOMWindow.isIE6){$('#DOMWindowIE6FixIframe').remove();}
				if(settings.functionCallOnClose){settings.functionCallAfterClose();}
			}	
		};
		
		if(settings.eventType){//if used with $().
			return this.each(function(index){
				$(this).bind(settings.eventType, function(){
					run(this);
					return false;
				});
			});
		}else{//else called as $.function
			run();
		}
		
	};
	
	//allow for public call, pass settings
	$.closeDOMWindow = function(s){$.fn.closeDOMWindow(s);};
	
	//openDOMWindow
	$.fn.openDOMWindow = function(instanceSettings){	
		
		var shortcut =  $.fn.openDOMWindow;
	
		//default settings combined with callerSettings////////////////////////////////////////////////////////////////////////
		
		shortcut.defaultsSettings = {
			anchoredClassName:'',
			anchoredSelector:'',
            anchor:null,
			borderColor:'#ccc',
			borderSize:'4',
			draggable:0,
			eventType:null, //click, blur, change, dblclick, error, focus, load, mousedown, mouseout, mouseup etc...
			fixedWindowY:100,
			functionCallOnOpen:null,
			functionCallOnClose:null,
			height:'auto',
			loader:0,
			loaderHeight:0,
			loaderImagePath:'',
			loaderWidth:0,
			modal:0,
			overlay:1,
			overlayColor:'#000',
			overlayOpacity:'85',
			positionLeft:0,
			positionTop:0,
			positionType:'centered', // centered, anchored, absolute, fixed
			width:'auto', 
			windowBGColor:'#fff',
			windowBGImage:null, // http path
			windowHTTPType:'get',
			windowPadding:10,
			windowSource:'inline', //inline, ajax, iframe
			windowSourceID:'',
			windowSourceURL:''
		};
		
		var settings = $.extend({}, $.fn.openDOMWindow.defaultsSettings , instanceSettings || {});
		
		//Public functions
		
		shortcut.viewPortHeight = function(){ return self.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;};
		shortcut.viewPortWidth = function(){ return self.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;};
		shortcut.scrollOffsetHeight = function(){ return self.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;};
		shortcut.scrollOffsetWidth = function(){ return self.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft;};
		shortcut.isIE6 = typeof document.body.style.maxHeight === "undefined";
        shortcut.absolutePosition = function(o){
            var p = o.position();
            p_left = parseInt(o.css('margin-left'));
            p_top  = parseInt(o.css('margin-top'));
            /** @fixme won't work for non-px margins */
            if (!isNaN(p_left))
                p.left += p_left;
            if (!isNaN(p_top))
                p.top += p_top;
            /** @fixme ? */
            oTagName = o.get(0).tagName.toLowerCase();
            if (oTagName!='html' && oTagName!='body' && o.offsetParent())
            {
                var pp = shortcut.absolutePosition(o.offsetParent());
                p.left += pp.left;
                p.top  += pp.top;
            }
            return p;
        }
        shortcut.getScroll = function(){
            var scroll = {x:0, y:0};
            if( typeof( window.pageYOffset ) == 'number' ) {
                //Netscape compliant
                scroll.y = window.pageYOffset;
                scroll.x = window.pageXOffset;
            } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
                //DOM compliant
                scroll.y = document.body.scrollTop;
                scroll.x = document.body.scrollLeft;
            } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
                //IE6 standards compliant mode
                scroll.y = document.documentElement.scrollTop;
                scroll.x = document.documentElement.scrollLeft;
            }
            return scroll;
        }
		
		//Private Functions/////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		var sizeOverlay = function(){
			if($.browser.msie){//if IE 6
				var overlayViewportHeight = document.documentElement.offsetHeight + document.documentElement.scrollTop - 4;
				var overlayViewportWidth = document.documentElement.offsetWidth - 21;
				$('#DOMWindowOverlay').css({'height':overlayViewportHeight +'px','width':overlayViewportWidth+'px'});
			}else{//else Firefox, safari, opera, IE 7+
				$('#DOMWindowOverlay').css({'height':'100%','width':'100%','position':'fixed'});
			}	
		};
		
		var sizeIE6Iframe = function(){
			var overlayViewportHeight = document.documentElement.offsetHeight + document.documentElement.scrollTop - 4;
			var overlayViewportWidth = document.documentElement.offsetWidth - 21;
			$('#DOMWindowIE6FixIframe').css({'height':overlayViewportHeight +'px','width':overlayViewportWidth+'px'});
		};
		
        /**
         * @fixme b0rkage?
         */
		var centerDOMWindow = function() {
            /*
			if(settings.height + 50 > shortcut.viewPortHeight()){//added 50 to be safe
				$('#DOMWindow').css('left',Math.round(shortcut.viewPortWidth()/2) + shortcut.scrollOffsetWidth() - Math.round(($('#DOMWindow').outerWidth())/2));
			}else{
            */
				$('#DOMWindow').css('left',Math.round(shortcut.viewPortWidth()/2) + shortcut.scrollOffsetWidth() - Math.round(($('#DOMWindow').outerWidth())/2));
				$('#DOMWindow').css('top',Math.round(shortcut.viewPortHeight()/2) + shortcut.scrollOffsetHeight() - Math.round(($('#DOMWindow').outerHeight())/2));
            /*
			}
            */
		};
		
		var centerLoader = function() {
			if(shortcut.isIE6){//if IE 6
				$('#DOMWindowLoader').css({'left':Math.round(shortcut.viewPortWidth()/2) + shortcut.scrollOffsetWidth() - Math.round(($('#DOMWindowLoader').innerWidth())/2),'position':'absolute'});
				$('#DOMWindowLoader').css({'top':Math.round(shortcut.viewPortHeight()/2) + shortcut.scrollOffsetHeight() - Math.round(($('#DOMWindowLoader').innerHeight())/2),'position':'absolute'});
			}else{
				$('#DOMWindowLoader').css({'left':'50%','top':'50%','position':'fixed'});
			}
			
		};

		var fixedDOMWindow = function(){
			$('#DOMWindow').css('left', settings.positionLeft + shortcut.scrollOffsetWidth());
			$('#DOMWindow').css('top', + settings.positionTop + shortcut.scrollOffsetHeight());
		};
		
		var showDOMWindow = function(instance){
			if(arguments[0]){
				$('.'+instance+' #DOMWindowLoader').remove();
				$('.'+instance+' #DOMWindowContent').fadeIn('fast',function(){if(settings.functionCallOnOpen){settings.functionCallOnOpen();}});
				$('.'+instance+ '.closeDOMWindow').click(function(){
					$.closeDOMWindow();	
					return false;
				});
			}else{
				$('#DOMWindowLoader').remove();
				$('#DOMWindow').fadeIn('fast',function(){if(settings.functionCallOnOpen){settings.functionCallOnOpen();}});
				$('#DOMWindow .closeDOMWindow').click(function(){						
					$.closeDOMWindow();
					return false;
				});
			}
            /**
             * @fixme a little glitchy.
             */
            $('#DOMWindow img').load(function(){
                $(this).css('display', 'inline');
                switch (settings.positionType)
                {
                    case 'centered' :
                        centerDOMWindow();
                        break;
                    case 'fixed' :
                        fixedDOMWindow();
                        break;
                }
            });
		};
		
		var urlQueryToObject = function(s){
			  var query = {};
			  s.replace(/b([^&=]*)=([^&=]*)b/g, function (m, a, d) {
				if (typeof query[a] != 'undefined') {
				  query[a] += ',' + d;
				} else {
				  query[a] = d;
				}
			  });
			  return query;
		};
			
		//Run Routine ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		var run = function(passingThis){
			
			//get values from element clicked, or assume its passed as an option
            // (changed behaviour)
            // get values from passed options, or assume they are in clicked element's href
			settings.windowSourceID = settings.windowSourceID || $(passingThis).attr('href');
			settings.windowSourceURL = settings.windowSourceURL || $(passingThis).attr('href');
			settings.windowBGImage = settings.windowBGImage ? 'background-image:url('+settings.windowBGImage+')' : '';
			var urlOnly, urlQueryObject;
			
			
			if(settings.positionType == 'anchored'){//anchored DOM window
				
                var anchor = $(settings.anchoredSelector);
                var anchoredPositions = shortcut.absolutePosition(anchor);
				var anchoredPositionX = anchoredPositions.left + settings.positionLeft;
				var anchoredPositionY = anchoredPositions.top + settings.positionTop + anchor.height() + parseInt(anchor.css('padding-top'));

                /**
                 * @fixme dirty, dirty hack..
                 *        the thing is -- on IE markers gets +scroll to their top/left even
                 *        thought they stay in the same position (and have position:absolute)
                 */
                if ($.browser.msie && anchor.parents('.map').length)
                {
                    var scroll = shortcut.getScroll();
                    anchoredPositionY -= scroll.y;
                    anchoredPositionX -= scroll.x;
                }
                //console.log('positions', anchoredPositions.top,settings.positionTop,anchor.height(),parseInt(anchor.css('padding-top')));
				
                var width = settings.width;
                if ($.browser.msie && $.browser.version<7)
                    width = 'width: '+width;
                else
                    width = 'max-width: '+width;
				$('body').append('<div class="'+settings.anchoredClassName+'" style="'+settings.windowBGImage+';background-repeat:no-repeat;padding:'+settings.windowPadding+'px;overflow:auto;position:absolute;top:'+anchoredPositionY+'px;left:'+anchoredPositionX+'px;height:'+settings.height+';width:'+settings.width+';background-color:'+settings.windowBGColor+';border:'+settings.borderSize+'px solid '+settings.borderColor+';z-index:10001;float:left"><div id="DOMWindowContent" style=""></div></div>');		
				//loader
				if(settings.loader && settings.loaderImagePath !== ''){
					$('.'+settings.anchoredClassName).append('<div id="DOMWindowLoader" style="width:'+settings.loaderWidth+';height:'+settings.loaderHeight+';"><img src="'+settings.loaderImagePath+'" /></div>');
					
				}

				if($.fn.draggable){
					if(settings.draggable){$('.' + settings.anchoredClassName).draggable({cursor:'move'});}
				}
				
				switch(settings.windowSource){
					case 'inline'://////////////////////////////// inline //////////////////////////////////////////
						$('.' + settings.anchoredClassName+" #DOMWindowContent").append($(settings.windowSourceID).html());
						$('.' + settings.anchoredClassName).unload(function(){// move elements back when you're finished
                            /** @fixme what gives? to w ogóle działa?... */
							//$('.' + settings.windowSourceID).append( $('.' + settings.anchoredClassName+" #DOMWindowContent").html());				
						});
						showDOMWindow(settings.anchoredClassName);
					break;
					case 'iframe'://////////////////////////////// iframe //////////////////////////////////////////
						$('.' + settings.anchoredClassName+" #DOMWindowContent").append('<iframe frameborder="0" hspace="0" wspace="0" src="'+settings.windowSourceURL+'" name="DOMWindowIframe'+Math.round(Math.random()*1000)+'" style="width:100%;height:100%;border:none;background-color:#fff;" class="'+settings.anchoredClassName+'Iframe" ></iframe>');
						$('.'+settings.anchoredClassName+'Iframe').load(showDOMWindow(settings.anchoredClassName));
					break;
					case 'ajax'://////////////////////////////// ajax //////////////////////////////////////////	
						if(settings.windowHTTPType == 'post'){
							
							if(settings.windowSourceURL.indexOf("?") !== -1){//has a query string
								urlOnly = settings.windowSourceURL.substr(0, settings.windowSourceURL.indexOf("?"));
								urlQueryObject = urlQueryToObject(settings.windowSourceURL);
							}else{
								urlOnly = settings.windowSourceURL;
								urlQueryObject = {};
							}
							$('.' + settings.anchoredClassName+" #DOMWindowContent").load(urlOnly,urlQueryObject,function(){
								showDOMWindow(settings.anchoredClassName);
							});
						}else{
							if(settings.windowSourceURL.indexOf("?") == -1){ //no query string, so add one
								settings.windowSourceURL += '?';
							}
							$('.' + settings.anchoredClassName+" #DOMWindowContent").load(
								settings.windowSourceURL + '&random=' + (new Date().getTime()),function(){
								showDOMWindow(settings.anchoredClassName);
							});
						}
					break;
				}
				
			}else{//centered, fixed, absolute DOM window
				
				//overlay & modal
				if(settings.overlay){
					$('body').append('<div id="DOMWindowOverlay" style="z-index:10000;display:none;position:absolute;top:0;left:0;background-color:'+settings.overlayColor+';filter:alpha(opacity='+settings.overlayOpacity+');-moz-opacity: 0.'+settings.overlayOpacity+';opacity: 0.'+settings.overlayOpacity+';"></div>');
					if(shortcut.isIE6){//if IE 6
						$('body').append('<iframe id="DOMWindowIE6FixIframe"  src="blank.html"  style="width:100%;height:100%;z-index:9999;position:absolute;top:0;left:0;filter:alpha(opacity=0);"></iframe>');
						sizeIE6Iframe();
					}
					sizeOverlay(); 
					$('#DOMWindowOverlay').fadeIn('fast');
					if(!settings.modal){$('#DOMWindowOverlay').click(function(){$.closeDOMWindow();});}
				}
				
				//loader
				if(settings.loader && settings.loaderImagePath !== ''){
					$('body').append('<div id="DOMWindowLoader" style="z-index:10002;width:'+settings.loaderWidth+';height:'+settings.loaderHeight+';"><img src="'+settings.loaderImagePath+'" /></div>');
					centerLoader();
				}

				//add DOMwindow
				$('body').append('<div id="DOMWindow" style="background-repeat:no-repeat;'+settings.windowBGImage+';overflow:auto;padding:'+settings.windowPadding+'px;display:none;height:'+settings.height+';width:'+settings.width+';background-color:'+settings.windowBGColor+';border:'+settings.borderSize+'px solid '+settings.borderColor+'; position:absolute;z-index:10001"></div>');
				
				//centered, absolute, or fixed
				switch(settings.positionType){
					case 'centered':
						centerDOMWindow();
                        /**
                         * @fixme possible b0rkage
                         */
						if(settings.height + 50 > shortcut.viewPortHeight()){//added 50 to be safe
							$('#DOMWindow').css('top', (settings.fixedWindowY + shortcut.scrollOffsetHeight()) + 'px');
						}
					break;
					case 'absolute':
						$('#DOMWindow').css({'top':(settings.positionTop+shortcut.scrollOffsetHeight())+'px','left':(settings.positionLeft+shortcut.scrollOffsetWidth())+'px'});
						if($.fn.draggable){
							if(settings.draggable){$('#DOMWindow').draggable({cursor:'move'});}
						}
					break;
					case 'fixed':
						fixedDOMWindow();
					break;
				}
				
				$(window).bind('scroll.DOMWindow',function(){
					if(settings.overlay){sizeOverlay();}
					if(shortcut.isIE6){sizeIE6Iframe();}
					if(settings.positionType == 'centered'){centerDOMWindow();}
					if(settings.positionType == 'fixed'){fixedDOMWindow();}
				});

				$(window).bind('resize.DOMWindow',function(){
					if(shortcut.isIE6){sizeIE6Iframe();}
					if(settings.overlay){sizeOverlay();}
					if(settings.positionType == 'centered'){centerDOMWindow();}
				});
				
				switch(settings.windowSource){
					case 'inline'://////////////////////////////// inline //////////////////////////////////////////
						$("#DOMWindow").append($(settings.windowSourceID).html());
						$("#DOMWindow").unload(function(){// move elements back when you're finished
                            /** @fixme again: what gives? to w ogóle działa?... */
							//$(settings.windowSourceID).append( $("#DOMWindow").html());				
						});
						showDOMWindow();
					break;
					case 'iframe'://////////////////////////////// iframe //////////////////////////////////////////
						$('#DOMWindow').append('<iframe frameborder="0" hspace="0" wspace="0" src="'+settings.windowSourceURL+'" name="DOMWindowIframe'+Math.round(Math.random()*1000)+'" style="width:100%;height:100%;border:none;background-color:#fff;" id="DOMWindowIframe" ></iframe>');
						$('#DOMWindowIframe').load(showDOMWindow());
					break;
					case 'ajax'://////////////////////////////// ajax //////////////////////////////////////////
						if(settings.windowHTTPType == 'post'){
							
							if(settings.windowSourceURL.indexOf("?") !== -1){//has a query string
								urlOnly = settings.windowSourceURL.substr(0, settings.windowSourceURL.indexOf("?"));
								urlQueryObject = urlQueryToObject(settings.windowSourceURL);
							}else{
								urlOnly = settings.windowSourceURL;
								urlQueryObject = {};
							}
							$("#DOMWindow").load(urlOnly,urlQueryObject,function(){
								showDOMWindow();
							});
						}else{
							if(settings.windowSourceURL.indexOf("?") == -1){ //no query string, so add one
								settings.windowSourceURL += '?';
							}
							$("#DOMWindow").load(
								settings.windowSourceURL + '&random=' + (new Date().getTime()),function(){
								showDOMWindow();
							});
						}
					break;
				}
				
			}//end if anchored, or absolute, fixed, centered
			
		};//end run()
		
		if(settings.eventType){//if used with $().
			return this.each(function(index){				  
				$(this).bind(settings.eventType,function(){
					run(this);
					return false;
				});
			});	
		}else{//else called as $.function
			run();
		}
		
	};//end function openDOMWindow
	
	//allow for public call, pass settings
	$.openDOMWindow = function(s){$.fn.openDOMWindow(s);};
	
})(jQuery);
