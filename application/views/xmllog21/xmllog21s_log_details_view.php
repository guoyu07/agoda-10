<script>$("#tabs").tabs();</script>
<style>textarea{border: 1px solid #006; width: 100%; height: 402px;}</style>
<div id="tabs">
    <ul>
        <li><a href="#tabs-id">ID</a></li>
        <li><a href="#tabs-xmlserv">XML Service</a></li>
        <li><a href="#tabs-confnum">Confirm Number</a></li>
        <li><a href="#tabs-rqlog">RQLog21</a></li>
        <li><a href="#tabs-rplog">RPLog21</a></li>
        <li><a href="#tabs-sprqlog">SupplierRQLog</a></li>
        <li><a href="#tabs-sprplog">SupplierRPLog</a></li>
        <li><a href="#tabs-credate">Create date</a></li>
    </ul>
    <div id="tabs-id">
        <textarea><?php echo $row['Id'] ?></textarea>
    </div>
    <div id="tabs-xmlserv">
        <textarea><?php echo $row['XMLService'] ?></textarea>
    </div>
    <div id="tabs-confnum">
        <textarea><?php echo $row['ConfirmNo'] ?></textarea>
    </div>
    <div id="tabs-rqlog">
        <textarea><?php echo $row['RQLog21'] ?></textarea>
    </div>
    <div id="tabs-rplog">
        <textarea><?php echo $row['RPLog21'] ?></textarea>
    </div>
    <div id="tabs-sprqlog">
        <textarea><?php echo $row['SupRQLog'] ?></textarea>
    </div>
    <div id="tabs-sprplog">
        <textarea><?php echo $row['SupRPLog'] ?></textarea>
    </div>
    <div id="tabs-credate">
        <textarea><?php echo $row['CreateDate'] ?></textarea>
    </div>
</div>