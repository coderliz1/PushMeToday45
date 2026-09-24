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
        const secondsRemaining = Math.max(
            0,
            Math.ceil((endingTime - Date.now()) / 1000)
        );

        displayTime(secondsRemaining);

        if (secondsRemaining === 0) {
            window.clearInterval(timer);
            finishTimer();
        }
    }, 250);
});


// Future Me mini-challenge button

document.addEventListener('DOMContentLoaded', () => {
    const challengeButton = document.getElementById(
        'futureChallengeButton'
    );

    const challengeConfirmation = document.getElementById(
        'futureChallengeConfirmation'
    );

    if (!challengeButton || !challengeConfirmation) {
        return;
    }

    challengeButton.addEventListener('click', () => {
        challengeButton.hidden = true;
        challengeConfirmation.hidden = false;
    });
});


// SOS Teach Me Something modal

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('sos-learning-modal');

    const triggers = document.querySelectorAll(
        '.sos-teach-trigger'
    );

    if (!modal || triggers.length === 0) {
        return;
    }

    const dialog = modal.querySelector(
        '.sos-learning-dialog'
    );

    const closeButtons = modal.querySelectorAll(
        '[data-sos-learning-close]'
    );

    const anotherButton = document.getElementById(
        'sos-learning-another'
    );

    const category = document.getElementById(
        'sos-learning-category'
    );

    const emoji = document.getElementById(
        'sos-learning-emoji'
    );

    const headline = document.getElementById(
        'sos-learning-headline'
    );

    const explanation = document.getElementById(
        'sos-learning-explanation'
    );

    const why = document.getElementById(
        'sos-learning-why'
    );

    const translation = document.getElementById(
        'sos-learning-translation'
    );

    const status = document.getElementById(
        'sos-learning-status'
    );

    const recentCategories = [];

    let previousFocus = null;
    let requestInProgress = false;

    const setLoading = (isLoading) => {
        requestInProgress = isLoading;
        anotherButton.disabled = isLoading;

        modal.classList.toggle(
            'is-loading',
            isLoading
        );

        status.textContent = isLoading
            ? 'Finding you a fresh fact…'
            : '';
    };

    const showFact = (fact) => {
        emoji.textContent = fact.emoji;
        category.textContent = fact.category;
        headline.textContent = fact.headline;
        explanation.textContent = fact.explanation;
        why.textContent = fact.why;
        translation.textContent = fact.translation;

        if (fact.category_key) {
            recentCategories.push(fact.category_key);

            if (recentCategories.length > 5) {
                recentCategories.shift();
            }
        }
    };

    const requestFact = async () => {
        if (requestInProgress) {
            return;
        }

        setLoading(true);

        const formData = new FormData();

        formData.append(
            'education_request',
            '1'
        );

        formData.append(
            'avoid_categories',
            JSON.stringify(recentCategories)
        );

        try {
            const response = await window.fetch(
                '/?page=sos',
                {
                    method: 'POST',

                    headers: {
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },

                    body: formData,
                }
            );

            if (!response.ok) {
                throw new Error(
                    'The learning request failed.'
                );
            }

            const result = await response.json();

            if (!result.success || !result.fact) {
                throw new Error(
                    'The learning response was incomplete.'
                );
            }

            showFact(result.fact);
        } catch (error) {
            setLoading(false);

            status.textContent =
                'I could not grab a new fact just now. ' +
                'Please try again.';

            return;
        }

        setLoading(false);
    };

    const openModal = (trigger) => {
        previousFocus = trigger;

        modal.hidden = false;

        document.body.classList.add(
            'sos-learning-open'
        );

        dialog.focus();
        requestFact();
    };

    const closeModal = () => {
        modal.hidden = true;

        document.body.classList.remove(
            'sos-learning-open'
        );

        if (previousFocus) {
            previousFocus.focus();
        }
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            openModal(trigger);
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener(
            'click',
            closeModal
        );
    });

    anotherButton.addEventListener(
        'click',
        requestFact
    );

    document.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape' &&
            !modal.hidden
        ) {
            closeModal();
        }
    });
});