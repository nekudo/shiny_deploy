function HelperMisc() {

    /**
     * Generates a unique (random) user-id.
     *
     * @returns {string}
     */
    this.getUuid = function () {
        var uuid = sessionStorage.getItem('uuid');
        if (uuid !== null) {
            return uuid;
        }
        uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
            return v.toString(16);
        });
        sessionStorage.setItem('uuid', uuid);
        return uuid;
    };

    /**
     * Returns current time
     *
     * @returns {string}
     */
    this.getTimeString = function() {
        var currentdate = new Date();
        return ((currentdate.getHours() < 10)?"0":"") + currentdate.getHours() +":"
            + ((currentdate.getMinutes() < 10)?"0":"") + currentdate.getMinutes() +":"
            + ((currentdate.getSeconds() < 10)?"0":"") + currentdate.getSeconds();
    }
}
