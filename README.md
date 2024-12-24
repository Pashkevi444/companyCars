# Документация проекта компонент букинга машин для моей компании
## Оглавление
1. [Пример обращения к методу](#пример-обращения-к-методу)

## Пример обращения к методу через js

```javascript
var url = '/bitrix/services/main/ajax.php?mode=class&c=all4it:cars.controller&action=getAvailableCars';
var params = new URLSearchParams({
  startTime: '2024-12-20 10:00',
    endTime: '2024-12-20 12:00',
    sessid: BX.message('bitrix_sessid')
});

var urlWithParams = url + '&' + params.toString();

fetch(urlWithParams)
.then(function(response) {
  if (!response.ok) {
    throw new Error('Ошибка при запросе: ' + response.statusText);
  }
  return response.text();
})
.then(function(responseText) {
  console.log('Ответ сервера:', responseText);
})
.catch(function(error) {
  console.error('Ошибка сети или запроса:', error);
});
```

