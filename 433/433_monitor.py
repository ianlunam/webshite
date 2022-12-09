#!/usr/bin/python3

import subprocess
import json
import pymysql
import sys

temp_insert = "insert into 433data values (%(id)s, %(model)s, %(time)s, %(stat)s, %(val)s)"

dbconfig = {
  "host": "192.168.0.250",
  "database": "temps",
  "user":     "temps",
  "password": "temps"
}
db = pymysql.connect(**dbconfig)
cursor = db.cursor()

proc = subprocess.Popen(['/usr/bin/rtl_433','-F','json','-q'],stdout=subprocess.PIPE)
while True:
  line = proc.stdout.readline()
  if line != '':
    data = json.loads(line.rstrip())
    if data['model'] == 'RadioHead-ASK':
        data['payload'] = bytearray(data['payload']).decode()
    print(json.dumps(data))
    for stat in data:
      if stat not in ['time', 'id', 'model']:
        temp_data = {
          "time": data['time'],
          "id": data['id'],
          "model": data['model'],
          "stat": stat,
          "val": str(data[stat])
        }
        try:
          cursor.execute(temp_insert, temp_data)
          db.commit()
        except pymysql.err.IntegrityError as err:
            # Bastard sent 2
            pass
        except Exception as err:
          print("Generic temp error: {0}".format(type(err)))
          print("Error: {0}".format(err))
          db = pymysql.connect(**dbconfig)
          cursor = db.cursor()
  else:
    break
  sys.stdout.flush()

cursor.close()
db.close()
