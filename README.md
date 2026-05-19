# Лабораторная работа №5. Облачные базы данных. Amazon RDS, DynamoDB

## Содержание

1. [Описание лабораторной работы](#описание-лабораторной-работы)
2. [Постановка задачи](#постановка-задачи)
3. [Цель и основные этапы работы](#цель-и-основные-этапы-работы)
4. [Шаг 1. Подготовка среды (VPC/подсети/SG)](#шаг-1-подготовка-среды-vpcподсетисg)
5. [Шаг 2. Развертывание Amazon RDS](#шаг-2-развертывание-amazon-rds)
6. [Шаг 3. Создание виртуальной машины для подключения к базе данных](#шаг-3-создание-виртуальной-машины-для-подключения-к-базе-данных)
7. [Шаг 4. Подключение к базе данных и выполнение базовых операций](#шаг-4-подключение-к-базе-данных-и-выполнение-базовых-операций)
8. [Шаг 5. Создание Read Replica](#шаг-5-создание-read-replica)
9. [Шаг 6. Подключение приложения к базе данных](#шаг-6-подключение-приложения-к-базе-данных)
10. [Вывод](#вывод)
11. [Веблиография](#веблиография)

## Описание лабораторной работы

В данной лабораторной работе выполняется развертывание и настройка облачной базы данных в Amazon Web Services с использованием сервисов Amazon RDS и Amazon DynamoDB. В рамках работы создается сетевая инфраструктура, настраиваются группы безопасности и подключение между EC2-инстансом и базой данных.

Далее создается экземпляр MySQL в Amazon RDS, выполняется подключение к нему с виртуальной машины EC2 и базовые операции с данными: создание таблиц, добавление записей и выполнение SQL-запросов. Дополнительно настраивается Read Replica, проверяется ее работа и рассматриваются особенности репликации данных в облачной среде.

На заключительном этапе выполняется подключение веб-приложения к базе данных Amazon RDS и проверяется корректная работа CRUD-операций.

## Постановка задачи

Необходимо развернуть и настроить облачную базу данных в Amazon Web Services с использованием сервисов Amazon RDS и Amazon DynamoDB, создать и настроить сетевую инфраструктуру, а также обеспечить безопасное подключение EC2-инстанса к базе данных.

Требуется:

- создать экземпляр MySQL в Amazon RDS;
- подключиться к базе с EC2;
- выполнить базовые операции с данными (DDL/DML/SELECT/JOIN);
- создать и протестировать Read Replica;
- подключить веб-приложение к RDS и проверить CRUD.

## Цель и основные этапы работы

Цель лабораторной работы: изучение сервисов Amazon RDS и Amazon DynamoDB, а также получение практических навыков работы с облачными базами данных в AWS.

В ходе выполнения работы осваиваются:

- создание и настройка экземпляров реляционных БД;
- подключение к БД через EC2;
- выполнение базовых операций с данными;
- настройка и проверка Read Replica;
- интеграция приложения с Amazon RDS.

Основные этапы:

- подготовка сетевой инфраструктуры и групп безопасности;
- создание и настройка Amazon RDS MySQL;
- подключение с EC2 и выполнение SQL-запросов;
- проверка работы репликации;
- подключение приложения к базе данных.

## Шаг 1. Подготовка среды (VPC/подсети/SG)

При создании VPC выбран режим **VPC and more**.

Автоматически создаются:

- VPC;
- public subnet;
- private subnet;
- route tables;
- Internet Gateway (IGW);
- NAT Gateway.

<img width="1194" height="559" alt="image" src="https://github.com/user-attachments/assets/05c006e3-c27e-4af3-9f07-88f082ca781a" />

Созданные ресурсы:

- VPC: `project-vpc`;
- Public subnets: `project-subnet-public1`, `project-subnet-public2`;
- Private subnets: `project-subnet-private1`, `project-subnet-private2`;
- Internet Gateway: `project-igw`;
- NAT Gateway: `project-nat`;
- Route Tables: public и private.

<img width="1044" height="441" alt="image" src="https://github.com/user-attachments/assets/581645fa-12e6-4c08-b6fd-add4670c3d5b" />

Создана `web-security-group` с входящими правилами:

- HTTP (порт 80) от любого источника;
- SSH (порт 22) от вашего IP-адреса или от любого источника (для учебных целей).

<img width="1045" height="386" alt="image" src="https://github.com/user-attachments/assets/0c60a395-65d2-48f9-ad7d-21655683b752" />

Создана `db-mysql-security-group` с входящим правилом:

- MySQL/Aurora (порт 3306) от `web-security-group`.

<img width="1045" height="344" alt="image" src="https://github.com/user-attachments/assets/6a0085dc-f1cc-468b-87ba-eb8849ad6ec3" />

Изменена `web-security-group`, добавлено исходящее правило:

- MySQL/Aurora (порт 3306) к `db-mysql-security-group`.
<img width="1045" height="243" alt="image" src="https://github.com/user-attachments/assets/a87258c9-3def-4fb2-9459-a77017c90c18" />

## Шаг 2. Развертывание Amazon RDS

Создана Subnet Group.

<img width="1045" height="475" alt="image" src="https://github.com/user-attachments/assets/38985f96-328a-458c-a570-390745b3158a" />

> **Вопрос:** Что такое Subnet Group? И зачем необходимо создавать Subnet Group для базы данных?
>
> **Ответ:** Subnet Group - это набор подсетей, в которых AWS может разместить базу данных RDS. Она нужна, чтобы база данных запускалась внутри нужной VPC, AWS мог разместить БД в разных Availability Zones, а сама база находилась в приватных подсетях, а не в публичных.

Создан экземпляр базы данных.

<img width="1045" height="495" alt="image" src="https://github.com/user-attachments/assets/5e5a278f-5693-48f5-8713-48f6c071eefb" />

<img width="1045" height="363" alt="image" src="https://github.com/user-attachments/assets/78548174-4696-4d11-bfec-3463462fbc06" />


Разделы настройки:

- настройка инстанса
  
<img width="1045" height="267" alt="image" src="https://github.com/user-attachments/assets/aa24e3b8-8f2a-45f8-b9e3-fe458c2afe33" />

- настройка Storage;
  
<img width="1045" height="494" alt="image" src="https://github.com/user-attachments/assets/4dc3f018-0881-4e19-81e2-b06b58852787" />

- раздел Connectivity;
  
<img width="1192" height="563" alt="image" src="https://github.com/user-attachments/assets/beb6c567-80db-483a-9afb-03a31d938318" />

- дополнительные параметры.
  
<img width="1192" height="578" alt="image" src="https://github.com/user-attachments/assets/6a94cb18-998b-44cc-be41-b3bdecf42db3" />


<img width="1192" height="215" alt="image" src="https://github.com/user-attachments/assets/7e6527eb-47d2-46ac-a301-437306a0e19c" />
Создание базы успешно завершено.

Endpoint для подключения:

- `project-rds-mysql-prod2.clawcms0eqa7.eu-central-1.rds.amazonaws.com`
  
<img width="1192" height="390" alt="image" src="https://github.com/user-attachments/assets/a3c88ad6-ac20-49ec-9e7a-7248fac79203" />

## Шаг 3. Создание виртуальной машины для подключения к базе данных

Создан EC2-инстанс для работы с базой данных.
<img width="895" height="656" alt="image" src="https://github.com/user-attachments/assets/afbff11f-6764-4cda-b0be-da50f9d21722" />

Настроены разделы:

- Network settings;
  
<img width="895" height="481" alt="image" src="https://github.com/user-attachments/assets/d2e2f012-b83d-4d58-959e-d0c1eb4ee828" />

- User data.

<img width="895" height="382" alt="image" src="https://github.com/user-attachments/assets/9cabd7d6-1d73-44c8-8aeb-5516bc3ec351" />


Подключение к инстансу успешно выполнено.

<img width="895" height="510" alt="image" src="https://github.com/user-attachments/assets/86192561-fb33-4954-b618-bd0069be1e27" />

## Шаг 4. Подключение к базе данных и выполнение базовых операций

Подключение к базе данных выполнено с EC2.

<img width="895" height="295" alt="image" src="https://github.com/user-attachments/assets/caf430b3-7350-4e73-a181-f1afaa281049" />

Созданы таблицы:

```sql
CREATE TABLE categories (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL
);

CREATE TABLE todos (
	id INT AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(255) NOT NULL,
	status VARCHAR(50),
	category_id INT,
	FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

Добавлены записи:

```sql
INSERT INTO categories (name)
VALUES
('Study'),
('Work'),
('Personal');

INSERT INTO todos (title, status, category_id)
VALUES
('Finish AWS lab', 'in progress', 1),
('Prepare report', 'done', 1),
('Send email', 'pending', 2);
```

<img width="895" height="525" alt="image" src="https://github.com/user-attachments/assets/a1ae9d97-f3fb-4a09-8131-61c8bf6cf6a1" />

Проверка показала, что данные заполнены корректно.

Выполнен JOIN-запрос:

```sql
SELECT
	todos.id,
	todos.title,
	todos.status,
	categories.name AS category
FROM todos
JOIN categories
ON todos.category_id = categories.id;
```

<img width="895" height="416" alt="image" src="https://github.com/user-attachments/assets/f79a3ee4-11ce-4d39-8701-64337a121402" />

Также выполнены CRUD-операции.

<img width="895" height="501" alt="image" src="https://github.com/user-attachments/assets/712bf6ac-8170-4b35-a73e-84f87c077bfc" />

## Шаг 5. Создание Read Replica

Read Replica настроена согласно требованиям.
<img width="895" height="471" alt="image" src="https://github.com/user-attachments/assets/64d03465-a910-403b-9271-1fb3911cdcc7" />

Этапы:

- ожидание успешного запуска;
<img width="895" height="249" alt="image" src="https://github.com/user-attachments/assets/f987386e-660b-4341-9264-95f1f2ef7a9c" />
- вход в консоль;
<img width="895" height="414" alt="image" src="https://github.com/user-attachments/assets/7ddf8cdc-363a-4b33-ae9c-bc3a670bf84c" />
- тестирование запросов.
<img width="895" height="445" alt="image" src="https://github.com/user-attachments/assets/4bbb9219-958b-453a-9a64-6d0c81b39889" />

> **Вопрос:** Какие данные вы видите? Объясните почему.
>
> **Ответ:** Видны те же данные, что и на основной базе данных, потому что Read Replica автоматически копирует данные с основного экземпляра RDS.

Проверка записи через `INSERT` на реплике:

<img width="895" height="131" alt="image" src="https://github.com/user-attachments/assets/e13b066e-7e38-4dcf-a061-45ef4a7327a6" />

> **Вопрос:** Получилось ли выполнить запись на Read Replica? Почему?
>
> **Ответ:** Запись не выполнилась, так как Read Replica предназначена только для чтения. AWS блокирует `INSERT`, `UPDATE`, `DELETE`, потому что реплика синхронизируется с основной базой и не должна изменяться отдельно.

Проверка синхронизации после записи в master:

<img width="899" height="611" alt="image" src="https://github.com/user-attachments/assets/6c11825d-d2e9-4e70-902b-c128caddefa6" />

> **Вопрос:** Отобразилась ли новая запись на реплике? Объясните почему.
>
> **Ответ:** Да, новая запись появилась на реплике через несколько секунд. Read Replica автоматически синхронизируется с основной базой данных и копирует все изменения.

> **Вопрос:** Для чего нужны Read Replicas?
>
> **Ответ:** Read Replicas используются для уменьшения нагрузки на основную базу, ускорения `SELECT`-запросов, масштабирования приложений, повышения отказоустойчивости и разделения операций чтения и записи.

> **Вопрос:** Где это полезно?
>
> **Ответ:** Это полезно в интернет-магазинах, социальных сетях, аналитических системах и приложениях с большим количеством пользователей и запросов на чтение данных. В таких системах основная база выполняет `INSERT/UPDATE/DELETE`, а реплики обрабатывают `SELECT`.

## Шаг 6. Подключение приложения к базе данных

Разработано простое приложение на PHP и настроено для развертывания.

Код приложения прикреплен в этом репозитории.

Логика подключения:

- запись и изменение данных выполняются через master instance;
- чтение данных выполняется через read replica.

## Вывод

В ходе лабораторной работы были получены практические навыки работы с облачными базами данных в Amazon Web Services. Создана сетевая инфраструктура с использованием VPC, публичных и приватных подсетей, а также настроены группы безопасности для безопасного взаимодействия между EC2-инстансом и базой данных.

Был развернут экземпляр Amazon RDS MySQL, выполнено подключение к базе данных с EC2-инстанса и реализованы базовые SQL-операции: создание таблиц, добавление записей, выполнение `SELECT`-запросов и `JOIN`. Также изучена работа Read Replica, проверена репликация данных и особенности разделения операций чтения и записи.

Дополнительно разработано и подключено простое PHP-приложение, работающее с Amazon RDS. В результате закреплены навыки настройки облачной инфраструктуры, подключения сервисов AWS и организации безопасной работы с базами данных в облачной среде.

## Веблиография

1. [Amazon Web Services ](https://aws.amazon.com/)
2. [Amazon RDS Documentation](https://docs.aws.amazon.com/rds/)
3. [MySQL Documentation](https://dev.mysql.com/doc/)
