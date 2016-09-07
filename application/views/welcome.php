<html>
    <head>
        <title><?php echo _SUPPLIERNAME; ?> sandbox</title>
        <link rel="stylesheet" href="public/style/main.css" type="text/css">
        <script src="public/script/jquery.min.js"></script>
        <script src="public/script/init.js"></script>
        <script src="public/script/request.js"></script>
        <style>
            .setRight
            {
                float: right;
            }

            .setLeft
            {
                float: left;
            }

        </style>
    </head>
    <body>
        <div class="container">

            <div class="mnu_header">
                <a class="setRight"  target="_blank"   href="<?php echo $supplier_url ?>"><img src="<?php echo $logo_url ?>"></a>
                <?php foreach ($mnu_header as $name => $item): ?>
                    <a href="<?php echo $item['href'] ?>" id="<?php echo $item['id'] ?>" class="set<?php echo $item['position'] ?>" title="<?php echo $item['title'] ?>"><?php echo $name ?></a>
                <?php endforeach; ?>

                <div style="clear: both;"></div>
            </div>
            <div class="notice pageWrapper">
                <span class="notice link">Read me before book hotels.</span>
                <span class="notice link">Use Ram : <?php echo $this->benchmark->memoryUsage(); ?></span>
            </div>
            <div class="pageWrapper">

                <form method="post" target="landing_area">
                    <label>DEBUG VALUE : </label><input type="number" name="Debug" min="0" max="1" value="0"/>
                    <textarea name="requestXML" id="requestXML" style="margin: 0px; width: 100%; height: 230px;"></textarea>
                    <input type="submit" value="request"/>
                </form>
                <iframe name='landing_area' style="margin-top: 5px;" frameborder="0" width="100%" height="100%"></iframe>
            </div>
        </div>

        <div class="<?php echo $ribbon['css'] ?> opacity"><?php echo $ribbon['envText'] ?></div>
        <!-- These are normally minified, I leave this block here so it can be uncommented during development. -->
        <script src="public/script/index.js"></script>
    </body>
</html>


