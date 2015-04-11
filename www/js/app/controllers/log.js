app.controller('LogController', function ($scope, $location, ws) {
    ws.registerWsEvent('log', function(eventData) {
        var message = eventData.text;
        var type = (typeof eventData.type !== 'undefined') ? eventData.type : 'default';
        var source = (typeof eventData.source !== 'undefined') ? eventData.source : '';
        var time = (typeof eventData.time !== 'undefined') ? eventData.time : getTimeString();
        log(message, type, source, time);
    });

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
   function log(message, type, source, time) {
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

    /**
     * Returns current time
     *
     * @returns {string}
     */
    function getTimeString()  {
        var currentdate = new Date();
        return ((currentdate.getHours() < 10)?"0":"") + currentdate.getHours() +":"
            + ((currentdate.getMinutes() < 10)?"0":"") + currentdate.getMinutes() +":"
            + ((currentdate.getSeconds() < 10)?"0":"") + currentdate.getSeconds();
    }
});