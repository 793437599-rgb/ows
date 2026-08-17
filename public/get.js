"use strict";  //严格模式

var page = require('webpage').create();
var system = require('system');
page.viewportSize = {
    width : 1150,
    height : 720
};

if (system.args.length < 3) {
    console.log('param must greater 2');
    phantom.exit();
} else{
    var url = system.args[1];  //远程视频地址
    var saveFile = system.args[2];  //保存截图的文件路径
    page.open(url, function(status) {
        if (status == 'success'){
            // 通过在JS获取页面的渲染高度
            var rect = page.evaluate(function () {
              return document.getElementsByTagName('html')[0].getBoundingClientRect();
            });
            // 按照实际页面的高度，设定渲染的宽高
            page.clipRect = {
              top:    rect.top,
              left:   rect.left,
              width:  rect.width,
              height: rect.height
            };
            setTimeout(function() {
                var result = page.render(saveFile);
                page.close();
                console.log(result);
                phantom.exit();
            }, 1000);  //延迟截图时间
        }
    })
}