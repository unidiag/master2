<?php

    if(isset($_GET["png"])){
        header("Content-Type: image/png");
        echo file_get_contents(__DIR__ . "/assets/meteo.png");
        exit;
    }

?><div id="93c4f3cde521c919056b7fb5a028a535" class="ww-informers-box-854753"><p class="ww-informers-box-854754"><a href="https://world-weather.ru/pogoda/belarus/chashniki/">Точный прогноз погоды в Чашниках</a></p></div><script async type="text/javascript" charset="utf-8" src="https://world-weather.ru/wwinformer.php?userid=93c4f3cde521c919056b7fb5a028a535"></script><style>.ww-informers-box-854754{-webkit-animation-name:ww-informers54;animation-name:ww-informers54;-webkit-animation-duration:1.5s;animation-duration:1.5s;white-space:nowrap;overflow:hidden;-o-text-overflow:ellipsis;text-overflow:ellipsis;font-size:12px;font-family:Arial;line-height:18px;text-align:center}@-webkit-keyframes ww-informers54{0%,80%{opacity:0}100%{opacity:1}}@keyframes ww-informers54{0%,80%{opacity:0}100%{opacity:1}}</style>
