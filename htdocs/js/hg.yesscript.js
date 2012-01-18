/**
 * yescript
 * --------
 * When <noscript> is not enough.
 *
 * params:
 *
 * - `(undefined|jQuery|selector) element`
 */
hg.yesscript.f = function (element) {

    var $yesscript;
    if (arguments.length === 0)
    {
        $yesscript = $('script[language="text/html+yesscript"]');
    }
    else
    {
        $yesscript = $(element);
    }

    $yesscript.each(function () {

        var $this = $(this);
        $('<div>', { html : $this.text() })
            .children()
                .insertAfter($this)
        ;
        $this.remove();

    });

};

