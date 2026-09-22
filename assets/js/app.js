document.addEventListener('DOMContentLoaded', () => {
    const countdown = document.querySelector('#sos-countdown');

    if (!countdown) {
        return;
    }

    const outcomes = document.querySelector('#sos-timer-outcomes');
    const message = document.querySelector('#sos-timer-message');

    const startingSeconds = Number.parseInt(
        countdown.dataset.seconds,
        10
    );

    if (
        !Number.isFinite(startingSeconds) ||
        startingSeconds <= 0
    ) {
        return;
    }

    const endingTime = Date.now() + (startingSeconds * 1000);

    const displayTime = (secondsRemaining) => {
        const minutes = Math.floor(secondsRemaining / 60);
        const seconds = secondsRemaining % 60;

        countdown.textContent =
            `${String(minutes).padStart(2, '0')}:` +
            `${String(seconds).padStart(2, '0')}`;
    };

    const finishTimer = () => {
        countdown.textContent = '00:00';

        if (message) {
            message.textContent =
                'You did it. Five minutes counts. What happens next?';
        }

        if (outcomes) {
            outcomes.hidden = false;
        }
    };

    displayTime(startingSeconds);

    const timer = window.setInterval(() => {
        const millisecondsRemaining = endingTime - Date.now();

        const secondsRemaining = Math.max(
            0,
            Math.ceil(millisecondsRemaining / 1000)
        );

        displayTime(secondsRemaining);

        if (secondsRemaining === 0) {
            window.clearInterval(timer);
            finishTimer();
        }
    }, 250);
});


// Future Me mini-challenge button

document.addEventListener('DOMContentLoaded', function () {
    const challengeButton = document.getElementById(
        'futureChallengeButton'
    );

    const challengeConfirmation = document.getElementById(
        'futureChallengeConfirmation'
    );

    if (!challengeButton || !challengeConfirmation) {
        return;
    }

    challengeButton.addEventListener('click', function () {
        challengeButton.hidden = true;
        challengeConfirmation.hidden = false;
    });
});