<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
        <link href="public/style/main.css" rel="stylesheet"/>
        <link href="//cdn.datatables.net/1.10.5/css/jquery.dataTables.min.css" rel="stylesheet"/>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css">
        <script src="public/script/jquery.min.js"></script>
        <script src="//cdn.datatables.net/1.10.5/js/jquery.dataTables.min.js"></script>
        <script src="//code.jquery.com/ui/1.11.3/jquery-ui.js"></script>
        <script>
            $(function () {
                $('form#frm_get_log_list').on('submit', function () {
                    $(".list_details").html('');
                    var postdata = $(this).serializeArray();
                    if (checkRequest(postdata) === false)
                    {
                        $.ajax({
                            url: 'xmllog21s/getlist',
                            data: postdata,
                            type: 'post'
                        }).done(function (response) {
                            $(".list_landing").html(response);
                        });
                    }
                    return false;
                });


                function checkRequest(postdata)
                {
                    var isError = false;
                    for (var i in postdata)
                    {
                        if (postdata[i].value === "")
                        {
                            isError = true;
                        } else
                        {
                            isError = false;
                            break;
                        }
                    }

                    if (isError)
                    {
                        alert("Please must be fill one or more field.");
                    }
                    return isError;
                }

            });
        </script>
    </head>
    <body>
        <!-- LOG XML DIALOG BOX-->
        <div class="container">
            <div class="mnu_header">
                <form method="post" action="" target="landing_area"  name="frmLog" id="frm_get_log_list">
                    <label>RQLog21 : </label><input type="text" name="RQLog21" id="RQLog21">
                    <label>RPLog21 : </label><input type="text" name="RPLog21" id="RPLog21">
                    <label>SupRQLog : </label><input type="text" name="SupRQLog" id="SupRQLog" >
                    <label>SupRPLog : </label><input type="text" name="SupRPLog" id="SupRPLog">
                    <label>CreateDate : </label><input type="text" name="CreateDate" id="CreateDate" value="<?php echo date('Y-m-d') ?>">
                    <input type="submit" class="sendrequestlog" value="Search">
                </form>
                <div style="clear: both;"></div>
            </div>
            <div class="list_landing"></div>
            <div class="list_details"></div>
        </div>

    </body>
</html>
