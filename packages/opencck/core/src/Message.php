<?php

namespace OpenCCK;

final class Message {
    public const USER_EXIST = 'Такой пользователь уже существует';
    public const USER_NOT_FOUND = 'Такого пользователя не существует';
    public const USER_NO_NOTIFICATION = 'У пользователя нет доступных способов восстановления доступа';
    public const USER_USERNAME_SHORT = 'Имя пользователя слишком короткое, минимум %s символов';
    public const USER_USERNAME_SAVED = 'Имя пользователя сохранено';
    public const USER_USERNAME_DESIRED = 'Введите желаемое имя пользователя';
    public const USER_PASSWORD_SHORT = 'Пароль слишком короткий, минимум %s символов';
    public const USER_PASSWORD_NEW = 'Введите новый пароль';
    public const USER_PASSWORD_CHANGED = 'Пароль успешно изменён';
    public const USER_USERNAME_FORMAT = 'Имя пользователя может состоять только из латинских букв и цифр';
    public const USER_ACCESS_ERROR = 'У Вашего пользователя нет доступа до административной панели';
    public const USER_NOTICE = 'Пользовательское уведомление';

    public const DATA_ERROR = 'Ошибка';
    public const DB_ERROR = 'Ошибка базы данных';

    public const NOTICE_RESET_SUBJECT = 'Восстановление доступа до аккаунта';
    public const NOTICE_RESET_TEXT = 'Кто-то, возможно Вы, пытаетесь восстановить доступ до аккаунта %s. Для восстановления используйте ссылку: %s';
    public const NOTICE_DO_RESET_SUBJECT = 'Новый пароль аккаунта';
    public const NOTICE_DO_RESET_TEXT = 'Для входа в аккаунт %s сгенерирован новый пароль: %s';

    public const TOKEN_ERROR = 'Указанный токен недействителен или устарел';
    public const TOKEN_ERROR_SIGNATURE = 'Указанный токен имеет недействительную подпись';
    public const TOKEN_ERROR_EXPIRED = 'Указанный токен устарел';

    public const BOT_METHOD_NOT_EXIST = 'Указанный метод %s не обнаружен';
}
