const SOUND_ON_ICON = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4Zm12.5 3a3.5 3.5 0 0 0-2-3.16v6.32A3.5 3.5 0 0 0 16.5 12Zm-2-8.58v2.06a7 7 0 0 1 0 13.04v2.06a9 9 0 0 0 0-17.16Z"/></svg>';
const SOUND_OFF_ICON = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4Zm12.59 3L14.5 9.91l1.41-1.41L18 10.59l2.09-2.09 1.41 1.41L19.41 12l2.09 2.09-1.41 1.41L18 13.41l-2.09 2.09-1.41-1.41L16.59 12Z"/></svg>';

function youtubeCommand(frame, command) {
    frame.contentWindow?.postMessage(JSON.stringify({
        event: 'command',
        func: command,
        args: []
    }), 'https://www.youtube-nocookie.com');
}

function soundButtonHost(media) {
    return media.closest('.program-campaign-cover, .program-section-hero, .program-section-content--background, .detail-program-hero, .post-hero')
        || media.parentElement;
}

function updateSoundButton(button, muted) {
    button.classList.toggle('is-unmuted', !muted);
    button.setAttribute('aria-pressed', String(!muted));
    button.setAttribute('aria-label', muted ? 'Nyalakan suara video' : 'Matikan suara video');
    button.innerHTML = `${muted ? SOUND_OFF_ICON : SOUND_ON_ICON}<span>${muted ? 'Nyalakan suara' : 'Matikan suara'}</span>`;
}

export function initManagedVideoAudio(root = document) {
    root.querySelectorAll('video[controls]').forEach(video => {
        video.defaultMuted = false;
        video.muted = false;
    });

    root.querySelectorAll('[data-managed-background-video]').forEach(media => {
        const host = soundButtonHost(media);
        if (!host || host.querySelector(':scope > [data-media-sound-toggle]')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'media-sound-toggle';
        button.dataset.mediaSoundToggle = '';
        updateSoundButton(button, true);
        host.append(button);

        if (media instanceof HTMLVideoElement) {
            media.defaultMuted = true;
            media.muted = true;
            media.addEventListener('volumechange', () => updateSoundButton(button, media.muted || media.volume === 0));
            media.addEventListener('error', () => { button.hidden = true; });
            button.addEventListener('click', async () => {
                const shouldMute = !(media.muted || media.volume === 0);
                media.muted = shouldMute;
                if (!shouldMute) media.volume = 1;
                updateSoundButton(button, shouldMute);
                if (!shouldMute && media.paused) {
                    try { await media.play(); } catch { media.muted = true; updateSoundButton(button, true); }
                }
            });
            return;
        }

        button.addEventListener('click', () => {
            const currentlyUnmuted = button.classList.contains('is-unmuted');
            youtubeCommand(media, currentlyUnmuted ? 'mute' : 'unMute');
            if (!currentlyUnmuted) youtubeCommand(media, 'playVideo');
            updateSoundButton(button, currentlyUnmuted);
        });
    });
}
