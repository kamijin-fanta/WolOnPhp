var deviceStatus = (function () {
    function deviceStatus() {
    }
    return deviceStatus;
})();
var arpResult = (function () {
    function arpResult() {
    }
    return arpResult;
})();

function deviceRefresh(reqId) {
    $(".device").each(function (i) {
        var id = $(this).data("deviceid");
        if (reqId !== "*" && id !== reqId)
            return true;

        $(this).removeClass("success loading fail").addClass("loading");

        $(this).find(".progress-bar").fadeTo(600, 1).width("50%");

        var responseTime = new Date().getTime();
        $.ajax("?m=ping&id=" + id, {
            type: "POST",
            data: {
                csrf: $("html").data("token")
            },
            deviceId: id,
            dataType: "json",
            success: function (data) {
                var d = data;
                var ddom = $(".device[data-deviceid=" + this.deviceId + "]");
                ddom.removeClass("loading");
                if (d.status === 2)
                    ddom.addClass("success");
                else
                    ddom.addClass("fail");
                ddom.find(".response-time").text(d.responseTime);

                ddom.find(".progress-bar").width("100%").delay(300).fadeTo(600, 0, function () {
                    ddom.find(".progress-bar").width("0%");
                });
            }
        });
    });
}

function getArpList() {
    $.ajax("?m=getlist", {
        type: "POST",
        data: {
            csrf: $("html").data("token")
        },
        dataType: "json",
        success: function (data) {
            var d = data;
            console.log(d);
            var list = $(".arp-result");
            list.empty();
            $.each(d, function (i, v) {
                $('<a href="" class="list-group-item"></a>').text(v.ip + " / " + v.mac).attr("data-ip", v.ip).attr("data-mac", v.mac).appendTo(list);
            });
        }
    });
}

$(function () {
    deviceRefresh("*");

    $(".all-refresh").click(function (e) {
        e.preventDefault();
        deviceRefresh("*");
    });
    $(".btn.refresh").click(function (e) {
        e.preventDefault();
        deviceRefresh($(this).parents(".device").eq(0).data("deviceid"));
    });

    $(".btn.list-refresh").click(function (e) {
        e.preventDefault();
        getArpList();
    });

    $(".arp-result").on("click", "a", function (e) {
        e.preventDefault();
        $("#inputIp").val($(this).data("ip"));
        $("#inputMac").val($(this).data("mac"));
    });

    $(".device .do").click(function (e) {
        e.preventDefault();
        var dev = $(this).parents(".device");
        var id = dev.data("deviceid");
        var method = (dev.hasClass("success")) ? "sleep" : "wake";

        dev.find(".progress-bar").addClass("progress-bar-success").fadeTo(600, 1).width("50%");

        $(this).parents(".device").removeClass("success loading fail").addClass("loading");

        $.ajax("?m=do&id=" + id + "&method=" + method, {
            type: "POST",
            data: {
                csrf: $("html").data("token")
            },
            dataType: "json",
            success: function (data) {
                var d = data;
                var ddom = $(".device[data-deviceid=" + d.id + "]");
                ddom.removeClass("loading").addClass("success");
                ddom.find(".progress-bar").width("100%").delay(300).fadeTo(600, 0, function () {
                    ddom.find(".progress-bar").removeClass("progress-bar-success").width("0%");
                });
            }
        });
    });
});

var text = { Text2: "{selector: \"p\"}" };
