$(function () {

    /**
     * 
     * @returns {String}
     */
    function getXMLQueries()
    {
        if (com.elm.hashtag.indexOf('Logdetail') > 1)
        {
            window.open("../../log/" + com.elm.hashtag.substring(0, 5) + "_post_detail.txt?" + com.date.timestamp, "landing_area");
            $('form').hide();
        }

        if (com.elm.hashtag.indexOf('xmllog21s') > 1)
        {
            window.open("xmllog21s", "landing_area");
            $('form').hide();
        }

        if (typeof queries[com.elm.hashtag] == 'undefined')
        {
            return 'no xml request please check again.';
        }

        $('form').show();
        //$('iframe').attr('src', 'about:blank');
        return queries[com.elm.hashtag].defaultQuery;
    }

    /**
     * 
     * @param {type} xml
     * @param {type} name
     * @returns {undefined}
     */
    function requestLanding(xml, name)
    {
        if (name === null)
        {
            name = 'requestXML';
        }

        document.getElementById('requestXML').value = xml.toString();
    }

    //Check hash tag change
    if (("onhashchange" in window)) {
        $(window).bind('hashchange', function () {
            com.elm.hashtag = window.location.hash.replace(/^#/, '');
            requestLanding(getXMLQueries());
        });

    } else {
        $('a.hash-changer').bind('click', function () {
            com.elm.hashtag = $(this).attr('href').replace(/^#/, '');
            requestLanding(getXMLQueries());
        });
    }

    //process for request.
    $('form').submit(function (e)
    {
        var $this_form = $(this);
        var xml = $(this).serializeArray();
        $.each(xml, function (i, v) {

            if (i === 1)
            {
                xmlDoc = $.parseXML(v.value);
                $xml = $(xmlDoc);

                //for Travflex request
                $travfObj = $xml.find('AgentLogin');
                //for side server [[SITEMINDER]]
                $outObj = $xml.find('Body');

                if ($travfObj.length > 0)
                {
                    $travfObj.each(function () {
                        var method = $(this).parent()[0].tagName;
                        var ext = 'php';

                        method = method.substring(method.indexOf('_') + 1);

                        if (method === 'SearchHotel')
                        {
                            method = method + 's';
                        }

                        if (method === 'CancelRSVN')
                        {
                            method = 'CancelRsvn';
                        }


                        $this_form.attr('action', method.substring(method.indexOf('_') + 1) + '.' + ext);
                    });
                }

                if ($outObj.length > 0)
                {
                    $outObj.each(function () {
                        var method = $(this).children()[0].tagName;
                        method = method.substring(method.indexOf('_') + 1);
                        method = method.substring(method.indexOf('RQ'), 0);
                        $this_form.attr('action', 'test_client.php');
                    });
                }
            }

        });
        return;
    });

    //fix :: log post details not response when looking twice.
    $('#Logdetail').on('click', function (e) {
        var supplier = $(this).attr('title');
        $(this).attr('href', '#' + supplier.substring(0, 5) + 'Logdetail');
        window.open("../../log/" + supplier.substring(0, 5) + "_post_detail.txt?" + com.date.timestamp, "landing_area");
        $('form').hide();
    });

    if (com.elm.hashtag.toString() != '')
    {
        requestLanding(getXMLQueries());
    }
});