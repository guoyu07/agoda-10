<style>
    .loading
    {
        display: none;
        position: absolute;
        z-index: 1;
        width: 100%;
        height: 100%;
        background: gray;
        filter: alpha(opacity=0.3);
        opacity: 0.3;

    }

    .font_loading{
        color: red;
        position: absolute;
        left: 48%;
        top: 30%;
    }

</style>
<script>

    var oTable = $('#example').DataTable({
        'data': <?php echo $dataSrc; ?>,
        /*"bJQueryUI": true,*/
        "columns": [
            {"title": "ID"},
            {"title": "XMLService"},
            {"title": "Create date"}
        ], "sPaginationType": "full_numbers",
        "bAutoWidth": false
    });

    $("#example tbody").hover(function () {
        $(this).css("cursor", "pointer");
    });

    $("#example tbody").click(function (event) {
        $(".loading").show();
        var LogID = $(event.target.parentNode).find("td:first").html();
        if (LogID !== "No data available in table") {
            $.ajax({
                url: "xmllog21s/getdetails",
                type: "POST",
                data: {"ID": LogID, },
                cache: false,
                beforeSend: function (xhr) {
                    xhr.overrideMimeType("text/plain; charset=x-user-defined");
                }
            }).done(function (data) {
                $(".loading").hide();
                $(".list_details").html(data);
            });
        }
        else
        {
            return false;
        }
    });
</script>
<div style="background: white;">
    <table cellpadding="0" cellspacing="0" border="0" class="display" id="example" style="width: 100%">
    </table>
</div>
<div class="loading"><h1 class="font_loading">LOADING</h></div>
