# Документация проекта  букинг машин для моей компании
## Оглавление
1. [Описание логики работы](#описание-логики-работы)
2. [Установка](#установка-модуля)
3. [Опции](#опции-модуля)


## Описание логики работы

## Установка модуля
* Для установки модуля необходимо поместить файлы модуля в папку `/local/modules/`
* Далее зайти в административной панели по пути `Рабочий стол -> Marketplace -> Установленные решения`
* Найти модуль  `paul.leadschecker` и начать его установку
* После установи модуля необходимо выполнить импорт хайлоадблоков из папки модуля `/company.cars/install/xmlMigrations`
* Переходим по пути  `Контент -> HighLoad блоки -> Экспорт/Импорт -> импорт`
* Далее Жмем на три точки рядом с первым полем `Файл для импорта`  и там выбираем файл из этой директории `/company.cars/install/xmlMigrations`
* В поле HighLoad блок Выбираем новый остальное оставляем и нажимаем кнопку **Импортировать**
* Так поступаем со вcеми xml файлами из папки миграций

После установки модуля новое поле Идентификатор будет создано и помещено в ЛИДЫ.  При удалении модуля поле будет удалено

## Опции модуля


