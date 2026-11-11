import Alpine from "alpinejs";

const REVEAL_DELAY_SECONDS = 1;

async function postJson(url, body = {}) {
    return fetch(url, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify(body),
    });
}

Alpine.data("quizPlay", (config) => ({
    prompt: config.prompt,
    options: config.options,
    usedLifelines: config.usedLifelines,
    hiddenOptionIds: config.hiddenOptionIds,
    score: config.score,
    questionNumber: config.questionNumber,
    totalQuestions: config.totalQuestions,
    secondsLeft: 0,
    answered: false,
    selectedId: null,
    correctId: null,
    loading: false,
    timer: null,

    init() {
        if (config.showLoader) {
            this.loading = true;
            setTimeout(() => {
                this.reveal(config.remainingSeconds - REVEAL_DELAY_SECONDS);
            }, REVEAL_DELAY_SECONDS * 1000);
        } else {
            this.reveal(config.remainingSeconds);
        }
    },

    reveal(secondsLeft) {
        this.loading = false;
        this.answered = false;
        this.selectedId = null;
        this.correctId = null;
        this.secondsLeft = secondsLeft;
        this.startTimer();
    },

    async useFiftyFifty() {
        // Disable the button right away - the server rejects a second claim
        // anyway (409), this just spares the pointless request.
        this.usedLifelines.push("fifty-fifty");

        const response = await postJson(config.fiftyFiftyUrl);

        if (response.ok) {
            const result = await response.json();
            this.hiddenOptionIds = result.hidden_option_ids;
        }
    },

    async useExtraTime() {
        this.usedLifelines.push("extra-time");
        this.stopTimer();

        const response = await postJson(config.extraTimeUrl);

        // Answered while the request was in flight - restarting the timer would
        // leak a second interval into the next question.
        if (this.answered) return;

        if (response.ok) {
            const result = await response.json();
            this.secondsLeft = result.remaining_seconds;
        }

        this.startTimer();
    },

    async useSkip() {
        this.usedLifelines.push("skip");
        this.stopTimer();

        // Lock the options before the request - a click in flight would land
        // on the next question as a forged answer.
        this.answered = true;

        const response = await postJson(config.skipUrl);

        if (response.ok) {
            const result = await response.json();
            this.handleResult(result);
        } else {
            this.answered = false;
            this.startTimer();
        }
    },

    startTimer() {
        this.timer = setInterval(() => {
            this.secondsLeft--;

            if (this.secondsLeft <= 0) {
                this.stopTimer();
                this.submit(null);
            }
        }, 1000);
    },

    stopTimer() {
        clearInterval(this.timer);
    },

    answer(optionId) {
        if (this.answered) return;

        this.stopTimer();
        this.submit(optionId);
    },

    async submit(optionId) {
        this.answered = true;
        this.selectedId = optionId;

        const response = await postJson(config.answerUrl, {
            option_id: optionId,
        });

        const result = await response.json();

        this.handleResult(result);
    },

    handleResult(result) {
        this.correctId = result.correct_country_id;
        this.score = result.score;
        this.questionNumber = result.question_number;

        setTimeout(() => {
            if (result.finished) {
                window.location.href = config.resultsUrl;
                return;
            }

            this.prompt = result.next_question.prompt;
            this.options = result.next_question.options;
            this.hiddenOptionIds = [];
            this.reveal(result.remaining_seconds - REVEAL_DELAY_SECONDS);
        }, REVEAL_DELAY_SECONDS * 1000);
    },

    optionClass(optionId) {
        if (!this.answered) {
            return "border-gray-300 bg-white hover:border-gray-900";
        }

        if (this.correctId === null) {
            return optionId === this.selectedId
                ? "border-gray-400 bg-gray-100"
                : "border-gray-300 bg-white opacity-50";
        }

        if (optionId === this.correctId) {
            return "border-green-600 bg-green-50";
        }

        if (optionId === this.selectedId) {
            return "border-red-600 bg-red-50";
        }

        return "border-gray-300 bg-white opacity-50";
    },
}));

window.Alpine = Alpine;
Alpine.start();
