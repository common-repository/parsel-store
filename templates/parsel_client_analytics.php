<?php

$ga_code = $data['ga_code'];

?>

<script type="text/javascript">

    if(typeof ga == 'undefined')
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');


    ga(function(){

        ga('create', '<?php echo $ga_code ?>', {
            name: "ParselTracker"
        });

        ga('ParselTracker.send', 'pageview', {
            'hitCallback': function() {
            }
        });
    });

</script>
