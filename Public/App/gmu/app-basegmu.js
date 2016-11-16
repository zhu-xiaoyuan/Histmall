//For Memory,For App//
//App-Wap-前端函数 V1.0//
//Auth:蒋金辰 App
//Mail:2094157689@qq.com
//(c) Copyright 2014 App. All Rights Reserved.

//App Alert重置
function App_gmuAlert(title, content, cancel, ok) {
    var cancelfun = cancel ? cancel : function () {
        this.destroy();
    };
    var okfun = ok ? ok : function () {
        this.destroy();
    };
    var opts = {
        'title': title,
        'content': content,
        'buttons': {
            '取消': cancelfun,
            '确定': okfun
        }
    };
    var alt = new gmu.Dialog(opts);
}

//时间倒计时消失
function App_gmuMsg(content, ok) {
    $('.ui-dialog').remove();
    var times = 2;//倒计时秒数
    var intval;
    var content1 = content + " (" + (times) + ")";
    var opts = {
        content: content1,
        closeBtn: false,
    };

    var alt = new gmu.Dialog(opts);
    var okfun = ok ? ok : function () {
        alt.destroy();
    };
    $('.ui-dialog-btns').remove();
    intval = setInterval(intfun, 1000);
    $('.ui-dialog').css({'border': 'none', 'background': 'none'});
    $('#ui-dialog-content').removeClass('ui-dialog-content');
    $('#ui-dialog-content').addClass('tip');
    $('.ui-mask').remove();

    function intfun() {
        if ((times - 1) < 1) {
            window.clearInterval(intval);
            setTimeout(okfun, 1);
            alt.destroy();
        } else {
            $('#ui-dialog-content').text(content + " (" + (times - 1) + ")");
            times--;
        }
    }
}
//时间倒计时消失
function zbb_msg(content, ok) {
    $('.ui-dialog').remove();
    var times = 2;//倒计时秒数
    var intval;
    var content1 = content + " (" + (times) + ")";
    var opts = {
        content: content1,
        closeBtn: false,
    };

    var alt = new gmu.Dialog(opts);
    var okfun = ok ? ok : function () {
        alt.destroy();
    };
    $('.ui-dialog-btns').remove();
    intval = setInterval(intfun, 1000);
    $('.ui-dialog').css({'border': 'none', 'background': 'none'});
    $('#ui-dialog-content').removeClass('ui-dialog-content');
    $('#ui-dialog-content').addClass('tip');
    $('.ui-mask').remove();

    function intfun() {
        if ((times - 1) < 1) {
            window.clearInterval(intval);
            setTimeout(okfun, 1);
            alt.destroy();
        } else {
            $('#ui-dialog-content').text(content + " (" + (times - 1) + ")");
            times--;
        }
    }
}

//确定和取消按钮
function zbb_confirm(content, ok, cancel) {
    var cancelfun = cancel ? cancel : function () {
        this.destroy();
    };
    var okfun = ok ? ok : function () {
        this.destroy();
    };
    var title = "温馨提示";

    var opts = {
        'title': title,
        'content': content,
        'buttons': {
            '取消': cancelfun,
            '确定': okfun
        }
    };
    var alt = new gmu.Dialog(opts);
}

//确定按钮
function zbb_alert(content, ok) {
    var okfun = ok ? ok : function () {
        this.destroy();
    };
    var title = "温馨提示";
    var opts = {
        'title': title,
        'content': content,
        'buttons': {
            '确定': okfun
        }
    };
    var alt = new gmu.Dialog(opts);
    return alt;
}

//input输入框按钮
function zbb_input(title, ok, cancel) {
    var cancelfun = cancel ? cancel : function () {
        this.destroy();
    };
    var okfun = ok ? ok : function () {
        this.destroy();
    };

    var opts = {
        'title': title,
        'buttons': {
            '取消': cancelfun,
            '确定': okfun
        }
    };
    var alt = new gmu.Dialog(opts);
    $('.ui-dialog').css('top', '100px');
    $('#ui-dialog-content').append('<div class="tx ovflw"><span class="iconfont">&#xe6ac</span><input type="number" id="inputobj"/></div>');
    $('#ui-dialog-content').append('<div style="height:10px"><span id="msgobj" style="font-size:0.8em;color:#ff3000;"></span></div>');
    $('#inputobj').focus();

}

//input输入框按钮
function zbb_input2(title, ok, cancel) {
    var cancelfun = cancel ? cancel : function () {
        this.destroy();
    };
    var okfun = ok ? ok : function () {
        var cardno = $('#inputno').val();
        var cardpwd = $('#inputpwd').val();
        $.post(url, {cardno: cardno, cardpwd: cardpwd}, function (data) {
            var json = JSON.parse(data);
            App_gmuMsg(json.info, function () {
                location.reload();
            });
        });
        this.destroy();
    };

    var opts = {
        'title': title,
        'buttons': {
            '取消': cancelfun,
            '确定': okfun
        }
    };
    var alt = new gmu.Dialog(opts);
    $('.ui-dialog').css('top', '100px');
    $('#ui-dialog-content').append('<div class="tx ovflw"><span class="iconfont">&#xe677</span><input type="text" id="inputno" placeholder="请填写卡号"/></div>');
    $('#ui-dialog-content').append('<div class="tx ovflw"><span class="iconfont">&#xe677</span><input type="text" id="inputpwd" placeholder="请填写密码"/></div>');
    $('#ui-dialog-content').append('<div style="height:10px"><span id="msgobj" style="font-size:0.8em;color:#ff3000;"></span></div>');
    //$('#inputno').focus();

}
