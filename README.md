# UUID

Simple TimeUUID and UUID library for distributed architecture.

## MicroUuid
Simple 96bit timeuuid realization that close to in-build PHP timestamp with microseconds.
Time precision will not be lost. Correctly sortable by time unlike UUID1(time uuid)
However you can't use MicroUuid for historical dates like UUID. So use this within range: 1970-01-01 - 2106-02-07.
MicroUuid if fully compatible with **32bit** architecture (Intel Atom, Celeron, RaspberryPi etc).
Y2038 problem will not affect MicroUuid as well.  

Why this so unique: every server and worker of fasts-cgi process has unique SEVER_ID and pid;
For the collision in only process should be generated more than 64 MicroUuid within single microsecond.
This is not possible even in a cycle without any other logic even if you overclock CPU up to 12Ghz.

It looks like hex string:
    `5e021ada-a5956-080127f-1cc6` (27 chars), where
   *  first part is _unix timestamp_ in seconds
   *  second part is _microseconds_
   *  third is _sequence_ and _pid_ (unix process id)
   *  and last is _server id_

or :
    `XgIkWjPAYEAaHQAA` - base64 encoded binary (16 chars)

 It need only **BINARY(12)** column to store, correctly sortable (by time with micros) 
 (hex string and base64 presentation is correctly sortable as well)

### ServerId
If you use multiple servers it is highly recommended to add unique `SERVER_ID=[0-65535]` in /etc/environment
(You could also find useful to add `SERVER_ROLE=[PROD|TEST|DEV]` as well)
Or just add in config:
`MicroUuid::$defaultServer = 12345;`

Alternative methods will be used for generating machine dependent _server id_ if you not set it directly.
