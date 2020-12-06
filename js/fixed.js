function sticky()
{

$(document).ready(function() {
        var summaries = $('#widget-11');
        summaries.each(function(i) {
            var summary = $(summaries[i]);
            var next = summaries[i + 1];

            summary.scrollToFixed({
                marginTop:54,
                limit: function() {
                    var limit = 0;
                    if (next) {
                        limit = $(next).offset().top - $(this).outerHeight(true) - 50;
                    } else {
                        limit = $('.footer').offset().top - $(this).outerHeight(true) - 54;
                    }
                    return limit;
                },
                zIndex: 100
            });
        });
        });
$(document).ready(function() {
        var summaries = $('#topmenu');
        summaries.each(function(i) {
            var summary = $(summaries[i]);
            var next = summaries[i + 1];

            summary.scrollToFixed({
                marginTop:0,
                limit: function() {
                    var limit = 0;
                    if (next) {
                        limit = $(next).offset().top - $(this).outerHeight(true) - 50;
                    } else {
                        limit = $('.footer').offset().top - $(this).outerHeight(true) - 40;
                    }
                    return limit;
                },
                zIndex: 9999
            });
        });
        });

} 

function detectcode() {
    if (navigator.userAgent.match(/Android/i)
            || navigator.userAgent.match(/webOS/i)
            || navigator.userAgent.match(/iPhone/i)
            || navigator.userAgent.match(/iPad/i)
            || navigator.userAgent.match(/iPod/i)
            || navigator.userAgent.match(/BlackBerry/i)
            || navigator.userAgent.match(/Windows Phone/i)
            || navigator.userAgent.match(/Opera Mini/i)
            || navigator.userAgent.match(/IEMobile/i)
            ) {
        isMobile = true;
    }
	else
	{
		sticky();	
	}
	
};

detectcode();
     

		