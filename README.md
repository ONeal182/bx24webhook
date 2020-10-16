
___
## Мини инструкция

Класс вызываем в файле /bitrix/php_interface/init.php



```php
AddEventHandler("sale", "OnOrderAdd", "OnOrderAddAddHandler");

function OnOrderAddAddHandler(&$ID, &$arFields) {
    $Bx24WbHook = new Bx24WbHook('веб хук который мы поулчем в б24','id созданной компании в б24',$arFields);
    $Bx24WbHook->creat_deal();
}
```

Как работает скрипт?

Мы вызываем событие "Добавление заказа", и получаем из него поля заказа.

Класс используйте в том случае если стандартная интеграция 1с-битрикс не работает