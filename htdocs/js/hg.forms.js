/**
 * Forms validation etc. tools
 */

/**
 * Should be binded to onkeyup
 */
hg['formsLimitLength'].f = function(that)
{
    that = that ? $(that) : $(this);

    var limit = that.attr('hg-maxlength');
    if (limit)
    {
        that.val(that.val().substr(0,limit));
    }
}

