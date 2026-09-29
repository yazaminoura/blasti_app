/*
 * Home hero background videos: plays every clip of data-videos in turn, each one fading into the next,
 * then starts the loop again. Two <video> tags: the next clip loads in the hidden one while the current plays.
 * Not started for "reduce motion" users: the drawn background shows instead.
 */
(function () {
    'use strict';

    const box = document.querySelector('.bl-hero-videos');
    if (!box) return;

    const clips = JSON.parse(box.dataset.videos || '[]');
    const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (calm || !clips.length) {
        box.closest('.hero-section-four').classList.remove('has-video');
        box.remove();
        document.querySelector('.bl-hero-shade')?.remove();
        return;
    }
    const players = Array.from(box.querySelectorAll('video'));
    const FADE_BEFORE_END = 1.2; // seconds: start the crossfade a little before the clip ends
    let current = 0;
    let index = 0;

    function load(player, i) {
        player.src = clips[i % clips.length];
        player.load();
    }

    function play(player) {
        const p = player.play();
        if (p && p.catch) p.catch(() => {}); // autoplay refused: the poster / background stays
    }

    function next() {
        const from = players[current];
        const to = players[1 - current];
        index = (index + 1) % clips.length;
        to.currentTime = 0;
        play(to);
        to.classList.add('is-on');
        from.classList.remove('is-on');
        current = 1 - current;
        // once faded out, the old player prepares the clip after this one
        setTimeout(() => { from.pause(); load(from, index + 1); }, 1200);
    }

    players.forEach((player) => {
        player.addEventListener('timeupdate', () => {
            if (player !== players[current] || player.dataset.fading) return;
            if (player.duration && player.currentTime >= player.duration - FADE_BEFORE_END) {
                player.dataset.fading = '1';
                if (clips.length > 1) next();
                else { player.currentTime = 0; delete player.dataset.fading; }
            }
        });
        player.addEventListener('playing', () => { delete player.dataset.fading; });
    });

    load(players[0], 0);
    play(players[0]);
    if (clips.length > 1) load(players[1], 1);
})();
