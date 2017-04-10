$(document).ready(function () {
    $('#type').change(function () {
        if ($(this).val()) {
            var paramName = 'type';
            var paramValue = $(this).val();
            var url = window.location.href;
            var hash = location.hash;

            if (url.indexOf(paramName + "=") >= 0) {
                var prefix = url.substring(0, url.indexOf(paramName));
                var suffix = url.substring(url.indexOf(paramName));
                suffix = suffix.substring(suffix.indexOf("=") + 1);
                suffix = (suffix.indexOf("&") >= 0) ? suffix.substring(suffix.indexOf("&")) : "";
                url = prefix + paramName + "=" + paramValue + suffix;
            } else {
                url += (url.indexOf("?") < 0 ? "?" : "&") + paramName + "=" + paramValue;
            }
            window.location.href = url + hash;
        }
    });
});
