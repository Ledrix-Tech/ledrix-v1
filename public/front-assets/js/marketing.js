(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var playBtn = document.getElementById('mktPlayBtn');
        var videoModal = document.getElementById('mktVideoModal');
        var closeModal = document.getElementById('mktVideoClose');
        var modalVideo = document.getElementById('mktModalVideo');

        if (!playBtn || !videoModal) {
            return;
        }

        function openVideo() {
            videoModal.classList.add('is-open');
            if (modalVideo) {
                modalVideo.play();
            }
        }

        function closeVideo() {
            videoModal.classList.remove('is-open');
            if (modalVideo) {
                modalVideo.pause();
                modalVideo.currentTime = 0;
            }
        }

        playBtn.addEventListener('click', openVideo);
        if (closeModal) {
            closeModal.addEventListener('click', closeVideo);
        }

        videoModal.addEventListener('click', function (event) {
            if (event.target === videoModal) {
                closeVideo();
            }
        });
    });
})();
