<?php
/**
 * 路由注册
 *
 * 以下代码为了尽量简单，没有使用路由分组
 * 实际上，使用路由分组可以简化定义
 * 并在一定程度上提高路由匹配的效率
 */

// 写完代码后对着路由表看，能否不看注释就知道这个接口的意义
use think\Route;

//面包销售表
Route::get('api/:version/getBreadByStoreId', 'api/:version.BreadSale/getBreadByStoreId');
Route::get('api/:version/getAllCats', 'api/:version.BreadSale/getAllCats');
Route::get('api/:version/getSaleByOprate', 'api/:version.BreadSale/getSaleByOprate');
Route::get('api/:version/getAllSaleByDate', 'api/:version.BreadSale/getAllSaleByDate');
Route::get('api/:version/getAllCatsByGroup', 'api/:version.BreadSale/getAllCatsByGroup');
Route::post('api/:version/editBreadSale', 'api/:version.BreadSale/editBreadSale');

//商品库存
Route::get('api/:version/getGoodsByStoreId', 'api/:version.Stock/getGoodsByStoreId');
Route::get('api/:version/getMonthByStoreId', 'api/:version.Stock/getMonthByStoreId');
Route::get('api/:version/getStockCats', 'api/:version.Stock/getStockCats');
Route::get('api/:version/getAllCatsByDate', 'api/:version.Stock/getAllCatsByDate');
Route::post('api/:version/editStock', 'api/:version.Stock/editStock');
Route::get('api/:version/getAllStockByMonth', 'api/:version.Stock/getAllStockByMonth');


//物料申请
Route::post('api/:version/submitApplyBuy', 'api/:version.ApplyBuy/submitApplyBuy');
Route::get('api/:version/searchGoods', 'api/:version.ApplyBuy/searchGoods');
Route::delete('api/:version/apply', 'api/:version.ApplyBuy/deleteApplyById');
Route::get('api/:version/apply/by_user', 'api/:version.ApplyBuy/getMyApply');
Route::get('api/:version/apply/by_id', 'api/:version.ApplyBuy/getApplyById');
Route::post('api/:version/apply/check', 'api/:version.ApplyBuy/checkApply');
// 确认收货记录 --20181026更新
Route::post('api/:version/submitArrival', 'api/:version.ApplyBuy/submitArrival');
//判断是否为超级管理员
Route::get('api/:version/admin', 'api/:version.ApplyBuy/is_admin');


//获取微信服务号的openi
Route::get('api/:version/openid', 'api/Wx/getOpenid');

// 请假申请记录  --20181026更新
Route::get('api/:version/apply/getMyApply', 'api/:version.ApplyLeave/getMyApply');
Route::post('api/:version/submitApplyLeave', 'api/:version.ApplyLeave/submitApplyLeave');
Route::get('api/:version/apply/leave_id', 'api/:version.ApplyLeave/getApplyById');
Route::post('api/:version/apply/checkleave', 'api/:version.ApplyLeave/checkApply');

//牛奶表
Route::get('api/:version/getMilkByStore', 'api/:version.Milk/getMilkByStoreId');
Route::get('api/:version/getMilkSaleByStore', 'api/:version.Milk/getMilkSaleByStoreId');
Route::post('api/:version/editMilk', 'api/:version.Milk/editMilk');

//饮料表
Route::get('api/:version/getDrinkByStore', 'api/:version.Drink/getDrinkByStoreId');
Route::get('api/:version/getDrinkSaleByStore', 'api/:version.Drink/getDrinkSaleByStoreId');
Route::post('api/:version/editDrink', 'api/:version.Drink/editDrink');
//Theme
// 如果要使用分组路由，建议使用闭包的方式，数组的方式不允许有同名的key
//Route::group('api/:version/theme',[
//    '' => ['api/:version.Theme/getThemes'],
//    ':t_id/product/:p_id' => ['api/:version.Theme/addThemeProduct'],
//    ':t_id/product/:p_id' => ['api/:version.Theme/addThemeProduct']
//]);

Route::group('api/:version/theme',function(){
    Route::get('', 'api/:version.Theme/getSimpleList');
    Route::get('/:id', 'api/:version.Theme/getComplexOne');
    Route::post(':t_id/product/:p_id', 'api/:version.Theme/addThemeProduct');
    Route::delete(':t_id/product/:p_id', 'api/:version.Theme/deleteThemeProduct');
});



//Product
Route::post('api/:version/product', 'api/:version.Product/createOne');
Route::delete('api/:version/product/:id', 'api/:version.Product/deleteOne');
Route::get('api/:version/product/by_category/paginate', 'api/:version.Product/getByCategory');
Route::get('api/:version/product/by_category', 'api/:version.Product/getAllInCategory');
Route::get('api/:version/product/:id', 'api/:version.Product/getOne',[],['id'=>'\d+']);
Route::get('api/:version/product/recent', 'api/:version.Product/getRecent');

//Category
Route::get('api/:version/category', 'api/:version.Category/getCategories'); 
// 正则匹配区别id和all，注意d后面的+号，没有+号将只能匹配个位数
//Route::get('api/:version/category/:id', 'api/:version.Category/getCategory',[], ['id'=>'\d+']);
//Route::get('api/:version/category/:id/products', 'api/:version.Category/getCategory',[], ['id'=>'\d+']);
Route::get('api/:version/category/all', 'api/:version.Category/getAllCategories');

//Token
Route::post('api/:version/token/user', 'api/:version.Token/getToken');

Route::post('api/:version/token/app', 'api/:version.Token/getAppToken');
Route::post('api/:version/token/verify', 'api/:version.Token/verifyToken');

//Address
Route::post('api/:version/address', 'api/:version.Address/createOrUpdateAddress');
Route::get('api/:version/address', 'api/:version.Address/getUserAddress');

//Order
Route::post('api/:version/order', 'api/:version.Order/placeOrder');
Route::get('api/:version/order/:id', 'api/:version.Order/getDetail',[], ['id'=>'\d+']);
Route::put('api/:version/order/delivery', 'api/:version.Order/delivery');

//不想把所有查询都写在一起，所以增加by_user，很好的REST与RESTFul的区别
Route::get('api/:version/order/by_user', 'api/:version.Order/getSummaryByUser');
Route::get('api/:version/order/paginate', 'api/:version.Order/getSummary');

//Pay
Route::post('api/:version/pay/pre_order', 'api/:version.Pay/getPreOrder');
Route::post('api/:version/pay/notify', 'api/:version.Pay/receiveNotify');
Route::post('api/:version/pay/re_notify', 'api/:version.Pay/redirectNotify');
Route::post('api/:version/pay/concurrency', 'api/:version.Pay/notifyConcurrency');

//Message
Route::post('api/:version/message/delivery', 'api/:version.Message/sendDeliveryMsg');





