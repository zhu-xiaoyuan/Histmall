gammaTool = {
    getInner: function () {
        if (typeof window.innerWidth == 'undefiend') {
            return {
                X: document.documentElement.clientWidth,
                Y: document.documentElement.clientHeight
            }
        } else {
            return {
                X: window.innerWidth,
                Y: window.innerHeight
            }
        }
    },
};
$.extend($.fn, {
    swiperThumb: function () {
        var _this = $(this);
        var w = gammaTool.getInner().X * 2;
        var h = parseInt(w * 2 / 5);
        $.each(_this, function () {
            var src = $(this).data("src");
            $(this).removeAttr("data-src");
            $(this).attr("src", src + "?imageView2/1/interlace/1/w/" + w + "/h/" + h);
        });
    },
    goodsIndex: function () {
        var _this = $(this);
        var w = parseInt((gammaTool.getInner().X - 34));
        var h = w;
        $.each(_this, function () {
            var src = $(this).data("src");
            //$(this).removeAttr("data-src"); //BUG 删除之后刷新，src的值undefined。
            $(this).attr("src", src + "?imageView2/1/interlace/1/w/" + w + "/h/" + h);
        });
    },
    goodsDatu: function () {
        var _this = $(this);
        var w = parseInt(gammaTool.getInner().X - 20) * 2;
        var h = parseInt(w * 3 / 5);
        $.each(_this, function () {
            var src = $(this).data("src");
            $(this).removeAttr("data-src");
            $(this).attr("src", src + "?imageView2/1/interlace/1/w/" + w + "/h/" + h);
        });
    },
    minThumb: function () {
        var _this = $(this);
        $.each(_this, function () {
            var src = $(this).data("src");
            //$(this).removeAttr("data-src");
            $(this).attr("src", src + "?imageView2/1/interlace/1/w/120/h/120");
        });
    }
});