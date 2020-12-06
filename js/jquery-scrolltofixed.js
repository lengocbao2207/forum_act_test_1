(function(e){e.isScrollToFixed=function(t){return!!e(t).data("ScrollToFixed")};e.ScrollToFixed=function(t,n){function m(){s.trigger("preUnfixed.ScrollToFixed");x();s.trigger("unfixed.ScrollToFixed");h=-1;f=s.offset().top;l=s.offset().left;if(r.options.offsets){l+=s.offset().left-s.position().left}if(c==-1){c=l}o=s.css("position");i=true;if(r.options.bottom!=-1){s.trigger("preFixed.ScrollToFixed");E();s.trigger("fixed.ScrollToFixed")}}function g(){var e=r.options.limit;if(!e)return 0;if(typeof e==="function"){return e.apply(s)}return e}function y(){return o==="fixed"}function b(){return o==="absolute"}function w(){return!(y()||b())}function E(){if(!y()){p.css({display:"none",width:s.outerWidth(true),height:s.outerHeight(true),"float":s.css("float")});cssOptions={position:"fixed",top:r.options.bottom==-1?N():"",bottom:r.options.bottom==-1?"":r.options.bottom,"margin-left":"0px"};if(!r.options.dontSetWidth){cssOptions["width"]=s.width()}s.css(cssOptions);s.addClass("scroll-to-fixed-fixed");if(r.options.className){s.addClass(r.options.className)}o="fixed"}}function S(){var e=g();var t=l;if(r.options.removeOffsets){t=0;e=e-f}cssOptions={position:"absolute",top:e,left:t,"margin-left":"0px",bottom:""};if(!r.options.dontSetWidth){cssOptions["width"]=s.width()}s.css(cssOptions);o="absolute"}function x(){if(!w()){h=-1;p.css("display","none");s.css({width:"",position:u,left:"",top:a.top,"margin-left":""});s.removeClass("scroll-to-fixed-fixed");if(r.options.className){s.removeClass(r.options.className)}o=null}}function T(e){if(e!=h){s.css("left",l-e);h=e}}function N(){var e=r.options.marginTop;if(!e)return 0;if(typeof e==="function"){return e.apply(s)}return e}function C(){if(!e.isScrollToFixed(s))return;var t=i;if(!i){m()}var n=e(window).scrollLeft();var o=e(window).scrollTop();var a=g();if(r.options.minWidth&&e(window).width()<r.options.minWidth){if(!w()||!t){L();s.trigger("preUnfixed.ScrollToFixed");x();s.trigger("unfixed.ScrollToFixed")}}else if(r.options.bottom==-1){if(a>0&&o>=a-N()){if(!b()||!t){L();s.trigger("preAbsolute.ScrollToFixed");S();s.trigger("unfixed.ScrollToFixed")}}else if(o>=f-N()){if(!y()||!t){L();s.trigger("preFixed.ScrollToFixed");E();h=-1;s.trigger("fixed.ScrollToFixed")}T(n)}else{if(!w()||!t){L();s.trigger("preUnfixed.ScrollToFixed");x();s.trigger("unfixed.ScrollToFixed")}}}else{if(a>0){if(o+e(window).height()-s.outerHeight(true)>=a-(N()||-k())){if(y()){L();s.trigger("preUnfixed.ScrollToFixed");if(u==="absolute"){S()}else{x()}s.trigger("unfixed.ScrollToFixed")}}else{if(!y()){L();s.trigger("preFixed.ScrollToFixed");E()}T(n);s.trigger("fixed.ScrollToFixed")}}else{T(n)}}}function k(){if(!r.options.bottom)return 0;return r.options.bottom}function L(){var e=s.css("position");if(e=="absolute"){s.trigger("postAbsolute.ScrollToFixed")}else if(e=="fixed"){s.trigger("postFixed.ScrollToFixed")}else{s.trigger("postUnfixed.ScrollToFixed")}}var r=this;r.$el=e(t);r.el=t;r.$el.data("ScrollToFixed",r);var i=false;var s=r.$el;var o;var u;var a;var f=0;var l=0;var c=-1;var h=-1;var p=null;var d;var v;var A=function(e){if(s.is(":visible")){i=false;C()}};var O=function(e){C()};var M=function(){var e=document.body;if(document.createElement&&e&&e.appendChild&&e.removeChild){var t=document.createElement("div");if(!t.getBoundingClientRect)return null;t.innerHTML="x";t.style.cssText="position:fixed;top:100px;";e.appendChild(t);var n=e.style.height,r=e.scrollTop;e.style.height="3000px";e.scrollTop=500;var i=t.getBoundingClientRect().top;e.style.height=n;var s=i===100;e.removeChild(t);e.scrollTop=r;return s}return null};var _=function(e){e=e||window.event;if(e.preventDefault){e.preventDefault()}e.returnValue=false};r.init=function(){r.options=e.extend({},e.ScrollToFixed.defaultOptions,n);r.$el.css("z-index",r.options.zIndex);p=e("<div />");o=s.css("position");u=s.css("position");a=e.extend({},s.offset());if(w())r.$el.after(p);e(window).bind("resize.ScrollToFixed",A);e(window).bind("scroll.ScrollToFixed",O);if(r.options.preFixed){s.bind("preFixed.ScrollToFixed",r.options.preFixed)}if(r.options.postFixed){s.bind("postFixed.ScrollToFixed",r.options.postFixed)}if(r.options.preUnfixed){s.bind("preUnfixed.ScrollToFixed",r.options.preUnfixed)}if(r.options.postUnfixed){s.bind("postUnfixed.ScrollToFixed",r.options.postUnfixed)}if(r.options.preAbsolute){s.bind("preAbsolute.ScrollToFixed",r.options.preAbsolute)}if(r.options.postAbsolute){s.bind("postAbsolute.ScrollToFixed",r.options.postAbsolute)}if(r.options.fixed){s.bind("fixed.ScrollToFixed",r.options.fixed)}if(r.options.unfixed){s.bind("unfixed.ScrollToFixed",r.options.unfixed)}if(r.options.spacerClass){p.addClass(r.options.spacerClass)}s.bind("resize.ScrollToFixed",function(){p.height(s.height())});s.bind("scroll.ScrollToFixed",function(){s.trigger("preUnfixed.ScrollToFixed");x();s.trigger("unfixed.ScrollToFixed");C()});s.bind("detach.ScrollToFixed",function(t){_(t);s.trigger("preUnfixed.ScrollToFixed");x();s.trigger("unfixed.ScrollToFixed");e(window).unbind("resize.ScrollToFixed",A);e(window).unbind("scroll.ScrollToFixed",O);s.unbind(".ScrollToFixed");r.$el.removeData("ScrollToFixed")});A()};r.init()};e.ScrollToFixed.defaultOptions={marginTop:0,limit:0,bottom:-1,zIndex:1e3};e.fn.scrollToFixed=function(t){return this.each(function(){new e.ScrollToFixed(this,t)})}})(jQuery)