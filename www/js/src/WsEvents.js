function WsEvents() {
    /**
     * Logs a message to console.
     *
     * Possible log-types are: default,info,success,danger,error
     *
     * @param {string} message
     * @param {string} type
     * @param {string} source
     * @param {string} time
     */
    this.log = function(message, type, source, time) {
        var elLog = $('#log');
        var logClass = 'log-' + type;
        var msgLines = message.split("\n");
        $.each(msgLines, function(i, msgLine) {
            var logMsg = '<div class="logMsg"><span class="log-time">' + time
                + '</span> <span class="log-source">' + source
                + '</span> <span class="' + logClass + '">' + msgLine + '</span></div>';
            var elLogMsg = $(logMsg);
            elLog.append(elLogMsg);
        });
        while ($('.logMsg').length > 2000) {
            elLog.find('logMsg').first().remove();
        }
        $(".nano").nanoScroller().nanoScroller({ scroll: 'bottom' });
    }
}