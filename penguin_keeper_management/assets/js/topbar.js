function updatePKMSClock() {
    const now = new Date();

    const dateElement = document.getElementById('pkms-date');
    const timeElement = document.getElementById('pkms-time');

    const dateOptions = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        timeZone: 'Asia/Kuala_Lumpur'
    };

    const timeOptions = {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'Asia/Kuala_Lumpur'
    };

    if (dateElement) {
        dateElement.textContent = new Intl.DateTimeFormat(
            'en-MY',
            dateOptions
        ).format(now);
    }

    if (timeElement) {
        timeElement.textContent = new Intl.DateTimeFormat(
            'en-MY',
            timeOptions
        ).format(now);
    }
}

updatePKMSClock();
setInterval(updatePKMSClock, 1000);
