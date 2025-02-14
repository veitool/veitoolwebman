/** 级联选择器模块 */
layui.define(['jquery'], function (exports) {
    var $ = layui.jquery;
    if ($('#v-css-cascader').length <= 0) {
        layui.link(layui.cache.base + 'cascader/cascader.css');
    }
    var onVisibleChangeList = [];  // 所有展开和关闭回调

    var cascader = {
        /* 初始化 */
        render: function (param) {
            // 默认参数
            var defaultOptions = {
                renderFormat: function (labels, values) {
                    return labels.join(' / ');
                },
                clearable: true,
                clearAllActive: false,
                disabled: false,
                trigger: 'click',
                changeOnSelect: false,
                filterable: false,
                notFoundText: '没有匹配数据'
            };
            param = $.extend(defaultOptions, param);
            var elem = param.elem;  // 目标元素
            var mDataList = param.data;  // 数据
            var renderFormat = param.renderFormat;  // 选择后用于展示的函数
            var clearable = param.clearable;  // 是否支持清除
            var clearAllActive = param.clearAllActive;  // 清除所有选中
            var disabled = param.disabled;  // 是否禁用
            var trigger = param.trigger;  // 次级菜单触发方式
            var changeOnSelect = param.changeOnSelect;  // 是否点击每项选项值都改变
            var reqData = param.reqData;  // 自定义获取数据的方法
            var filterable = param.filterable;  // 是否开启搜索
            var notFoundText = param.notFoundText;  // 搜索列表为空时显示的内容
            var reqSearch = param.reqSearch;  // 自定义搜索的方法
            var onChange = param.onChange;  // 数据选择完成的回调
            var onVisibleChange = param.onVisibleChange;  // 展开和关闭弹窗时触发
            var itemHeight = param.itemHeight;  // 下拉列表每一项高度
            var isFirst = true;
            // 如果渲染过重新渲染
            var $elem = $(elem);
            if ($elem.next().hasClass('v-cascader-group')) {
                $elem.next().remove();
                for (var i = 0; i < onVisibleChangeList.length; i++) {
                    if (elem == onVisibleChangeList[i].elem) {
                        onVisibleChangeList.splice(i, 1);
                        break;
                    }
                }
            }
            onVisibleChangeList.push({elem: elem, onVisibleChange: onVisibleChange});
            $elem.addClass('v-cascader-hide');
            var htmlStr = '<div class="v-cascader-group">';
            htmlStr += '      <div class="v-cascader-input-group">';
            htmlStr += '         <input class="layui-input v-cascader-input" readonly/>';
            htmlStr += '         <input class="layui-input v-cascader-input-search"/>';
            htmlStr += '         <i class="layui-icon layui-icon-triangle-d v-icon-arrow"></i>';
            htmlStr += '         <i class="layui-icon layui-icon-loading-1 layui-anim layui-anim-rotate layui-anim-loop v-icon-loading"></i>';
            htmlStr += '         <i class="layui-icon layui-icon-close-fill v-icon-clear"></i>';
            htmlStr += '      </div>';
            htmlStr += '      <div class="v-cascader-dropdown layui-anim layui-anim-upbit"></div>';
            htmlStr += '      <div class="v-cascader-search-list"></div>';
            htmlStr += '   </div>';
            $elem.after(htmlStr);

            var $cascader = $elem.next();
            var $inputGroup = $cascader.children('.v-cascader-input-group');
            var $input = $inputGroup.children('.v-cascader-input');
            var $inputSearch = $inputGroup.children('.v-cascader-input-search');
            var $dropdown = $cascader.children('.v-cascader-dropdown');
            var $search = $cascader.children('.v-cascader-search-list');

            $input.attr('placeholder', $elem.attr('placeholder'));
            disabled && $input.addClass('layui-disabled');

            // 构建渲染后的实例
            var _instance = {
                data: mDataList,
                getData: function () {
                    return mDataList;
                },
                /* 展开 */
                open: function () {
                    if ($cascader.hasClass('v-cascader-open')) {
                        return;
                    }
                    cascader.hideAll();
                    $cascader.addClass('v-cascader-open');
                    cascader.checkWidthPosition($dropdown);  // 溢出屏幕判断
                    cascader.checkHeightPosition($dropdown);  // 溢出屏幕判断
                    onVisibleChange && onVisibleChange(true);  // 展开回调
                    if (filterable) {  // 如果开启搜索功能让输入框获取焦点
                        $inputGroup.addClass('show-search');
                        $inputSearch.focus();
                    }
                },
                /* 关闭 */
                hide: function () {
                    if (!$cascader.hasClass('v-cascader-open')) {
                        return;
                    }
                    $cascader.removeClass('v-cascader-open');
                    $cascader.removeClass('dropdown-show-top');
                    $cascader.removeClass('dropdown-show-left');
                    cascader.hideAllSearch();
                    onVisibleChange && onVisibleChange(false);  // 关闭回调
                },
                /* 移除加载中的状态*/
                removeLoading: function () {
                    $cascader.removeClass('show-loading');
                    $dropdown.find('.v-cascader-dropdown-list-item').removeClass('show-loading');
                },
                /* 设置禁用状态 */
                setDisabled: function (dis) {
                    disabled = dis;
                    if (dis) {
                        $input.addClass('layui-disabled');
                        _instance.hide();
                    } else {
                        $input.removeClass('layui-disabled');
                    }
                },
                /* 获取值*/
                getValue: function () {
                    return $elem.val();
                },
                /* 获取label */
                getLabel: function () {
                    return $input.val();
                },
                /* 设置值*/
                setValue: function (value) {
                    if (value == undefined || value == null || !value.toString()) {
                        $input.val('');
                        $elem.val('');
                        if (clearAllActive || changeOnSelect) {  // 清除所有
                            $dropdown.children('.v-cascader-dropdown-list').not(':first').remove();
                            $dropdown.find('.v-cascader-dropdown-list-item').removeClass('active');
                            cascader.checkWidthPosition($dropdown);  // 溢出屏幕判断
                        } else {  // 仅清除最后一项
                            $dropdown.find('.v-cascader-dropdown-list-item.is-last').removeClass('active');
                        }
                        $inputGroup.removeClass('show-clear');
                        return;
                    }
                    value = value.toString().split(',');
                    var labels = [];

                    // 通过递归控制异步加载回显默认值对应label时的请求顺序
                    function doReqData(tValues, data, i, values, callback) {
                        if (!tValues && data) {
                            tValues = [];
                            setData(data);
                        } else if (tValues && data && data.children) {
                            setData(data.children);
                        } else {  // 数据不存在时才去请求数据
                            $cascader.addClass('show-loading');
                            reqData(tValues, function (dataList) {
                                if (tValues) {
                                    data.children = dataList;
                                } else {
                                    mDataList = dataList;
                                    tValues = [];
                                }
                                setData(dataList);
                            }, data);
                        }

                        function setData(dataList) {
                            for (var j = 0; j < dataList.length; j++) {
                                if (dataList[j].value == values[i]) {
                                    labels[i] = dataList[j].text;
                                    tValues[i] = dataList[j].value;
                                    if (i < values.length - 1) {
                                        doReqData(tValues, dataList[j], i + 1, values, callback);
                                    } else {
                                        callback();
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    doReqData(undefined, mDataList, 0, value, function () {
                        $cascader.removeClass('show-loading');
                        $input.val(renderFormat(labels, value));
                        $elem.val(value.join(','));
                    });
                }
            };

            // 回显初始值
            _instance.setValue($elem.val());

            // 点击展开/关闭下拉列表
            $inputGroup.off('click').on('click', function (e) {
                // 判断是否是禁用状态
                if ($input.hasClass('layui-disabled')) {
                    return;
                }
                // 判断是否是加载中状态
                if ($cascader.hasClass('show-loading')) {
                    return;
                }
                // 关闭
                if ($cascader.hasClass('v-cascader-open')) {
                    if (!filterable) {  // 是否开启搜索功能
                        _instance.hide();
                    }
                    return;
                }
                // 展开
                if (isFirst) {  // 第一次展开渲染第一列数据
                    isFirst = false;
                    if (mDataList) {
                        renderList($dropdown, mDataList, undefined, itemHeight);
                        initLabel();
                        _instance.open();
                    } else {  // 异步方式
                        $cascader.addClass('show-loading');
                        reqData(undefined, function (dataList) {
                            mDataList = dataList;
                            renderList($dropdown, dataList, undefined, itemHeight);
                            $cascader.removeClass('show-loading');
                            _instance.open();
                        }, undefined);
                    }
                } else {
                    initLabel();
                    _instance.open();
                }

                // 回显上次选中项
                function initLabel() {
                    var value = $elem.val().toString();
                    if (value) {
                        value = value.split(',');
                        for (var i = 0; i < value.length; i++) {
                            var $item = $dropdown.children('.v-cascader-dropdown-list').eq(i).children('.v-cascader-dropdown-list-item[data-value="' + value[i] + '"]');
                            //if (i == value.length - 1) { /* 注释这些修复第一次弹出下拉项时，回显时漏掉最后一级不显示 */
                                //$item.addClass('active'); 
                            //} else {
                                $item.trigger('click');
                            //}
                        }
                    } else {
                        _instance.setValue();
                    }
                }

                //顶部定位处理
                $dropdown.find('.v-cascader-dropdown-list').each(function(){
                    var $item = $(this).find('.active');
                    if($item.length){
                        var height = $(this).innerHeight() - 39, top = $item.position().top - 5;
                        if(top > height || top < 0) $(this).scrollTop(top + $(this).scrollTop());
                    }
                });

            });
            $inputGroup.children('.v-icon-arrow').off('click').on('click', function (e) {
                if ($cascader.hasClass('v-cascader-open')) {
                    _instance.hide();
                    e.stopPropagation();
                }
            });

            // 点击渲染次级列表
            $dropdown.off('click').on('click', '.v-cascader-dropdown-list-item', function () {
                var $this = $(this);
                // 防止重复点击
                if ($this.hasClass('active')) {
                    if ($this.hasClass('is-last')) {
                        _instance.hide();
                    }
                    return;
                }
                // 判断是否是禁用状态
                if ($this.hasClass('v-cascader-disabled')) {
                    return;
                }
                // 判断是否是加载中状态
                if ($this.parent().parent().find('.v-cascader-dropdown-list-item').hasClass('show-loading')) {
                    return;
                }
                var index = $this.data('index').toString();
                var indexList = index.split('-');
                var data = mDataList[parseInt(indexList[0])], values = [data.value], labels = [data.text];
                for (var i = 1; i < indexList.length; i++) {
                    data = data.children[parseInt(indexList[i])];
                    values[i] = data.value;
                    labels[i] = data.text;
                }
                if (data.haveChildren) {  // 非最后一项
                    if (data.children) {  // 数据方式或已经异步加载直接渲染
                        $this.parent().nextAll().remove();
                        cascader.checkWidthPosition($dropdown);  // 检查是否溢出屏幕
                        activeThis();
                        renderList($dropdown, data.children, index, itemHeight);
                    } else {  // 异步方式先请求数据再渲染
                        $this.addClass('show-loading');
                        reqData(values, function (dataList) {
                            data.children = dataList;
                            $this.parent().nextAll().remove();
                            cascader.checkWidthPosition($dropdown);  // 检查是否溢出屏幕
                            activeThis();
                            renderList($dropdown, dataList, index, itemHeight);
                            $this.removeClass('show-loading');
                        }, data);
                    }
                    // 点击非最后一项也触发选中
                    if (changeOnSelect) {
                        activeThis();
                        doChange();
                    }
                } else {  // 最后一项
                    $this.parent().nextAll().remove();
                    activeThis();
                    doChange();
                    _instance.hide();  // 选中后关闭
                }

                /* 选中当前 */
                function activeThis() {
                    $this.parent().children('.v-cascader-dropdown-list-item').removeClass('active');
                    $this.addClass('active');
                }

                /* 触发选中回调 */
                function doChange() {
                    $input.val(renderFormat(labels, values));  // 赋值label
                    $elem.val(values.join(','));  // 赋值value
                    $elem.removeClass('layui-form-danger');  // 移除表单验证
                    onChange && onChange(values, data);  // 选中回调
                }
            });

            // hover方式触发
            if (trigger == 'hover') {
                $dropdown.off('mouseenter').on('mouseenter', '.v-cascader-dropdown-list-item', function () {
                    if (!$(this).hasClass('is-last')) {
                        $(this).trigger('click');
                    }
                });
            }

            // 开启清除功能
            if (clearable) {
                $inputGroup.off('mouseenter').on('mouseenter', function () {
                    if ($elem.val().toString() && !$input.hasClass('layui-disabled')) {
                        $(this).addClass('show-clear');
                    }
                });
                $inputGroup.off('mouseleave').on('mouseleave', function () {
                    $(this).removeClass('show-clear');
                });
                // 点击清除
                $inputGroup.children('.v-icon-clear').off('click').on('click', function (e) {
                    e.stopPropagation();
                    _instance.setValue();
                    onChange && onChange();  // 选中回调
                });
            }

            // 开启搜索功能
            if (filterable) {
                $inputSearch.off('input').on('input', function () {
                    var value = $(this).val();
                    if (!value) {
                        $cascader.removeClass('show-search-list');
                        $inputGroup.removeClass('have-value');
                        return;
                    }
                    $inputGroup.addClass('have-value');
                    if (reqSearch) {  // 异步搜索
                        reqSearch(value, function (rsList) {
                            // 渲染搜索结果
                            renderSearchList($search, rsList, notFoundText);
                            $cascader.addClass('show-search-list');
                        }, mDataList);
                    } else {  // 前端搜索
                        var allList = [], rsList = [];

                        // 把树形list转一维list
                        function toAllList(arr, label, value, disabled) {
                            for (var i = 0; i < arr.length; i++) {
                                var item = arr[i];
                                item.__label = label ? label + ' / ' + item.text : item.text;
                                item.__value = value ? value + ',' + item.value : item.value;
                                item.__disabled = item.disabled ? item.disabled : disabled;

                                if (item.children && item.children.length) {
                                    toAllList(item.children, item.__label, item.__value, item.__disabled);
                                    delete item.__label;
                                    delete item.__value;
                                } else {
                                    allList.push({
                                        text: item.__label,
                                        value: item.__value,
                                        disabled: item.__disabled
                                    });
                                }
                            }
                        }

                        toAllList(mDataList);

                        // 过滤数据
                        for (var i = 0; i < allList.length; i++) {
                            var item = allList[i];
                            if (item.text.indexOf(value) > -1) {
                                item.text = item.text.replace(new RegExp(value, 'g'), '<span class="search-keyword">' + value + '</span>');
                                rsList.push(item);
                            }
                        }
                        // 渲染搜索结果
                        renderSearchList($search, rsList, notFoundText);
                        $cascader.addClass('show-search-list');
                    }
                });
                $search.off('click').on('click', '.v-cascader-search-list-item', function (e) {
                    e.stopPropagation();
                    if ($(this).hasClass('v-cascader-disabled')) {  // 是否禁用
                        return;
                    }
                    var value = $(this).data('value').toString();
                    _instance.hide();
                    _instance.setValue(value);
                    var values = value.split(',');
                    var data = _instance.getData();
                    for (var i = 0; i < values.length; i++) {
                        for (var j = 0; j < data.length; j++) {
                            if (data[j].value == values[i]) {
                                if (i === values.length - 1) {
                                    data = data[j];
                                } else {
                                    data = data[j].children;
                                }
                                break;
                            }
                        }
                    }
                    onChange && onChange(values, data);  // 选中回调
                });
            }

            return _instance;
        },
        /** 关闭所有 */
        hideAll: function () {
            cascader.hideAllSearch();
            for (var i = 0; i < onVisibleChangeList.length; i++) {
                var elem = onVisibleChangeList[i].elem;
                var onVisibleChange = onVisibleChangeList[i].onVisibleChange;
                var $cascader = $(elem).next();
                if ($cascader.hasClass('v-cascader-open')) {
                    $cascader.removeClass('v-cascader-open');
                    $cascader.removeClass('dropdown-show-top');
                    $cascader.removeClass('dropdown-show-left');
                    onVisibleChange && onVisibleChange(false);
                }
            }
        },
        /** 关闭所有搜索面板 */
        hideAllSearch: function () {
            $('.v-cascader-input-group').removeClass('show-search');
            $('.v-cascader-group').removeClass('show-search-list');
            $('.v-cascader-input-group').removeClass('have-value');
            $('.v-cascader-input-search').val('');
        },
        /* 获取浏览器高度 */
        getPageHeight: function () {
            return document.documentElement.clientHeight || document.body.clientHeight;
        },
        /* 获取浏览器宽度 */
        getPageWidth: function () {
            return document.documentElement.clientWidth || document.body.clientWidth;
        },
        /* 检查宽度是否溢出屏幕 */
        checkWidthPosition: function ($dropdown) {
            if ($dropdown.offset().left + $dropdown.outerWidth() > cascader.getPageWidth()) {
                $dropdown.parent().addClass('dropdown-show-left');
            } else {
                $dropdown.parent().removeClass('dropdown-show-left');
            }
        },
        /* 检查高度是否溢出屏幕 */
        checkHeightPosition: function ($dropdown) {
            if ($dropdown.offset().top + $dropdown.outerHeight() > cascader.getPageHeight()) {
                $dropdown.parent().addClass('dropdown-show-top');
                if ($dropdown.offset().top < 0) {
                    $dropdown.parent().removeClass('dropdown-show-top');
                }
            }
        }
    };

    /* 处理省市区数据，value变中文 */
    cascader.getCityData = function (data) {
        for (var i = 0; i < data.length; i++) {
            data[i].value = data[i].text;
            if (data[i].children) {
                data[i].children = cascader.getCityData(data[i].children);
            }
        }
        return data;
    };

    /* 处理省市区数据，不要区域 */
    cascader.getCity = function (data) {
        for (var i = 0; i < data.length; i++) {
            for (var j = 0; j < data[i].children.length; j++) {
                delete data[i].children[j].children;
            }
        }
        return data;
    };

    /* 处理省市区数据，只要省 */
    cascader.getProvince = function (data) {
        for (var i = 0; i < data.length; i++) {
            delete data[i].children;
        }
        return data;
    };

    /** 渲染列表 */
    var renderList = function ($dropdown, dataList, pIndex, itemHeight) {
        var style = itemHeight ? ' style="height:' + itemHeight + ';"' : '';
        var htmlStr = '<div class="v-cascader-dropdown-list" ' + style + '>';
        for (var i = 0; i < dataList.length; i++) {
            var item = dataList[i];
            var index = pIndex == undefined ? i : (pIndex + '-' + i);
            if (item.haveChildren == undefined) {
                item.haveChildren = item.children ? true : false;
            }
            var className = item.haveChildren ? '' : ' is-last';  // 是否是叶子节点
            item.disabled && (className += ' v-cascader-disabled');  // 是否禁用
            htmlStr += '   <div class="v-cascader-dropdown-list-item' + className + '" data-index="' + index + '" data-value="' + item.value + '">' + item.text + '<i class="layui-icon layui-icon-right v-icon-right"></i><i class="layui-icon layui-icon-loading-1 layui-anim layui-anim-rotate layui-anim-loop v-icon-loading"></i></div>';
        }
        htmlStr += '   </div>';
        $dropdown.append(htmlStr);
        cascader.checkWidthPosition($dropdown);  // 检查是否溢出屏幕
    };

    /** 渲染搜索列表 */
    var renderSearchList = function ($search, dataList, notFoundText) {
        var htmlStr = '';
        if (dataList.length == 0) {
            htmlStr = '<div class="v-cascader-search-list-empty">' + notFoundText + '</div>';
        } else {
            for (var i = 0; i < dataList.length; i++) {
                var item = dataList[i];
                var className = item.disabled ? ' v-cascader-disabled' : '';  // 是否禁用
                htmlStr += '<div class="v-cascader-search-list-item' + className + '" data-value="' + item.value + '">' + item.text + '</div>';
            }
        }
        $search.html(htmlStr);
    };

    // 点击空白区域关闭下拉列表
    $(document).off('click.cascader').on('click.cascader', function (e) {
        try {
            var classNames = e.target.className.split(' ');
            var cascaders = ['v-cascader-group', 'v-cascader-input', 'v-icon-arrow', 'v-cascader-dropdown', 'v-cascader-dropdown-list', 'v-cascader-dropdown-list-item', 'v-icon-right', 'v-cascader-input-search', 'v-cascader-search-list', 'v-cascader-search-list-item'];
            for (var i in classNames) {
                for (var j in cascaders) {
                    if (classNames[i] == cascaders[j]) {
                        return;
                    }
                }
            }
        } catch (e) {
        }
        cascader.hideAll();
    });

    exports('cascader', cascader);
});