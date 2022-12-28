<style>
    .goog-te-banner-frame,
    .goog-te-gadget {
        display: none !important;
    }

    div #google_trans {
        z-index: 999999999999 !important;
    }

    .goog-te-combo {
        position: absolute !important;
        left: 50%;
        z-index: 9999999999999999 !important;
    }
</style>


<div id="google_trans"></div>

<script src="https://translate.google.com/translate_a/element.js?cb=translateInitialize"></script>
<script>
     function translateInitialize() {
        let trans;
        trans = new google.translate.TranslateElement({
            pageLanguage: 'en',
            autoDisplay: true,
            skipTranslate: 'skiptranslate'
        }, 'google_trans');

    }

    let appendtrans = setInterval(function() {
        try {
            if (document.querySelector('#google_trans') && document.querySelector('.goog-te-combo')) {
                document.querySelector('#google_trans').append(document.querySelector('.goog-te-combo'));
                clearInterval(appendtrans);
            }

        } catch (err) {

        }

    }, 500);
</script>