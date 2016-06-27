<?php
$str_var_ac = $_GET["ac"];
$str_var_tp = $_GET["tp"];
$str_var_d = $_GET["d"];

$isDemoMode = isset($str_var_d);

$animatedcontent = unserialize(base64_decode($str_var_ac));
$topicproperties = unserialize(base64_decode($str_var_tp));
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Pre-U | <?php echo $topicproperties['title']; ?></title>
    <meta name="description" content="De animatie inhoud voor de online leeromgeving van Twente Academy voor scholieren en docenten">
    <meta name="keywords" content="online, leren, scholieren, twente academy, leeromgeving, animatie, uitleg">
    <meta name="viewport" content="width=device-width, user-scalable=no, minimal-ui">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="apple-touch-icon" href="assets/images/touch-icon-iphone.png">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/images/touch-icon-ipad.png">
    <link rel="apple-touch-icon" sizes="120x120" href="assets/images/touch-icon-iphone-retina.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/images/touch-icon-ipad-retina.png">
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <link rel="stylesheet" href="assets/viewer.css">
    <script>
    var receiver;
    var it;
    window.onload = function() {
        var slidesize = {};
        slidesize.w = 1024;
        slidesize.h = 728;

        window.currentIndex = 0;
        window.contentList = [];

        <?php
            $arr = '[';
            foreach ($animatedcontent as $content) {
                $arr .= '{ "name" : "'.$content["name"].'", "file" : "'.$topicproperties["root"].'/'.$content["file"].'"},';
            }
            $arr .= ']';

            echo 'window.contentList = '.$arr;
        ?>

        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            document.documentElement.className += " mobile";
        }

        var setViewport = function() {
            var viewportmeta = document.querySelector('meta[name="viewport"]');
            var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
            var height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);


            var ratio = slidesize.w / slidesize.h;
            var screenratio = width / height;

            var viewportwidth = slidesize.w;
            if (screenratio > ratio) {
                var a = Math.min(width, height);
                var b = Math.max(width, height);
                viewportwidth = Math.floor((slidesize.h / a) * b);
            }
            viewportmeta.content = viewportmeta.content.replace(/width=[^,]+/, 'width=' + viewportwidth);
            window.scroll(0, 5);
        };

        setViewport();

        window.addEventListener('orientationchange', setViewport);
        window.addEventListener('resize', setViewport);


        window.currentIndex = 0;

        // Get the window displayed in the iframe.
        receiver = document.getElementById('theframe').contentWindow;
        var dm = new DemoMode();
        it = new inactivityTimer();

        // Get a reference to the previous/next button.
        var btnPrev = document.getElementById('btn_prev');
        var btnNext = document.getElementById('btn_next');

        // A function to handle sending messages.
        function nemoPrevious(e) {
            // Prevent any default browser behaviour.
            e.preventDefault();

            // Send a message with the text 'Hello Treehouse!' to the new window.
            receiver.postMessage('previous', '*');
            it.reset();
        }

        // A function to handle sending messages.
        function nemoNext(e) {
            // Prevent any default browser behaviour.
            e.preventDefault();

            // Send a message with the text to the iframe
            receiver.postMessage('next', '*');
            it.reset();
        }

        var spnIndex = document.getElementById('spn_ind');
        var spnTotal = document.getElementById('spn_total');
        var nav = document.getElementById('navigation');

        // Create IE + others compatible event handler
        var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
        var eventer = window[eventMethod];
        var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";

        function showOverlay() {
            var ele = document.getElementById("overlay");
            ele.className = ele.className.replace("hide", "show");
            setTimeout(function() {
                ele.style.display = "block";
            }, 200);
        }

        function hideOverlay() {
            var ele = document.getElementById("overlay");
            ele.className = ele.className.replace("show", "hide");
            setTimeout(function() {
                ele.style.display = "none";
            }, 200);

            var iframe = document.getElementById("theframe");
            iframe.contentWindow.focus();
        }

        function addListeners() {
            btnPrev.addEventListener('click', nemoPrevious);
            btnNext.addEventListener('click', nemoNext);
            btnPrev.classList.remove('disabled');
            btnNext.classList.remove('disabled');
        }

        function removeListeners() {
            btnPrev.removeEventListener('click', nemoPrevious);
            btnNext.removeEventListener('click', nemoNext);
            btnPrev.classList.add('disabled');
            btnNext.classList.add('disabled');
        }

        function verticallyAlignFrame() {
            var height = receiver.innerHeight;
            if(height > 813) {
                var topOffset = (height - 813)*0.5;
                var viewport = receiver.document.getElementById('viewport');
                if(viewport) {
                    viewport.style.top = topOffset + 'px';
                }
            }
        }

        // Listen to message from child window
        eventer(messageEvent, function(e) {
            var obj = JSON.parse(e.data);
            if (obj) {
                if (obj.load) {
                    // align window vertical centered
                    verticallyAlignFrame();
                    window.addEventListener('resize', verticallyAlignFrame);

                    hideOverlay();
                    addListeners();
                    it.start();
                    if(isDemoMode()) {
                        dm.start();
                    }
                }
                if (obj.atBound !== undefined) {
                    var direction = obj.atBound ? 'next' : 'previous';

                    dm.pause();
                    it.pause();

                    if (direction === 'next') {
                        if (window.currentIndex < (window.contentList.length - 1)) {
                            showOverlay();
                            document.getElementById("theframe").src = window.contentList[window.currentIndex + 1].file;
                            document.getElementById("contentname").innerHTML = window.contentList[window.currentIndex + 1].name;
                            currentIndex++;
                            removeListeners();
                        } else {
                            // end of file!
                            if(isDemoMode()) {
                                window.location.href = './';
                            }
                        }
                    } else {
                        if (window.currentIndex > 0) {
                            showOverlay();
                            document.getElementById("theframe").src = window.contentList[window.currentIndex - 1].file;
                            document.getElementById("contentname").innerHTML = window.contentList[window.currentIndex - 1].name;
                            currentIndex--;
                            removeListeners();
                        }
                    }

                }
                if (obj.displayTerm !== undefined)
                    console.log(obj.displayTerm);
                if (obj.err !== undefined)
                    hideOverlay();
                //alert('Some error occured: ' + obj.err.message);
            }

        }, false);

        document.getElementById("theframe").src = window.contentList[0].file;
    };

    function isDemoMode() {
        return document.body.classList.contains('demo');
    }

    var inactivityTimer = function() {
        var timer; 
        var initialized = false;

        function init() {
            document.addEventListener('mousemove', reset);
            document.addEventListener('keypress', reset);
            document.addEventListener('touchstart', reset);
            initialized = true;
        }

        function start() {
            if(!initialized) init();
            reset();
        }

        function setTimer() {
            timer = setInterval(function() {
                window.location.href = './'; // go to main page
            }, 300000);
        }

        function reset() {
            clearInterval(timer);
            setTimer();
        }

        function abort() {
            clearInterval(timer);
            document.removeEventListener('mousemove', reset);
            document.removeEventListener('keypress', reset);
            document.removeEventListener('touchstart', reset);
        }

        function pause() {
            clearInterval(timer);
        }

        return {
            start: start,
            pause: pause,
            abort: abort,
            reset: reset
        };
    };

    var DemoMode = function() {
        var timer; 
        var initialized = false;

        function init() {
            document.addEventListener('mousemove', reset);
            document.addEventListener('keypress', reset);
            document.addEventListener('touchstart', reset);
            initialized = true;
        }

        function start() {
            if(!initialized) init();

            timer = setInterval(function() {
                receiver.postMessage('next', '*');
                if(it) it.reset();
            }, 10000);
        }

        function abort() {
            reset();
            document.removeEventListener('mousemove', reset);
            document.removeEventListener('keypress', reset);
            document.removeEventListener('touchstart', reset);
            initialized = false;
        }

        function reset() {
            clearInterval(timer);
        }

        return {
            abort : abort,
            start : start,
            pause : reset
        };
    };
    </script>
</head>

<body<?php if($isDemoMode) { echo ' class="demo"'; } ?>>
    <!-- Overlay for loading purposes -->
    <div id="overlay" class="overlay show">
        <div class="content">
            <svg width="72px" height="72px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-default">
                <rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(0 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(30 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.08333333333333333s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(60 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.16666666666666666s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(90 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.25s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(120 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.3333333333333333s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(150 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.4166666666666667s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(180 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.5s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(210 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.5833333333333334s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(240 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.6666666666666666s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(270 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.75s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(300 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.8333333333333334s' repeatCount='indefinite' />
                </rect>
                <rect x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='#FFF' transform='rotate(330 50 50) translate(0 -30)'>
                    <animate attributeName='opacity' from='1' to='0' dur='1s' begin='0.9166666666666666s' repeatCount='indefinite' />
                </rect>
            </svg>
        </div>
    </div>
    <!--- The iframe for all content -->
    <main>
        <iframe id="theframe" width="100%" height="100%">
            <p>Your browser does not support iframes.</p>
        </iframe>
    </main>
    <!--- Navigation bar -->
    <nav>
        <a id="btn_prev" href="#" class="move prev disabled">&lt;&lt;&lt;&lt;</a>
        <a id="btn_menu" href="./" class="breadcrumb">
            <li>Pre-U</li>
            <li><?php echo $topicproperties['title']; ?></li>
            <li id="contentname"><?php echo $animatedcontent[0]['name']; ?></li>
        </a>
        <a id="btn_next" href="#" class="move next disabled">&gt;&gt;&gt;&gt;</a>
    </nav>
</body>

</html>
