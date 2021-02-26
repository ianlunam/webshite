#!/usr/bin/python3

import subprocess
import json
import pymysql
import sys

oil_insert = "insert into oiltank values (%(time)s, %(temp)s, %(depth)s)"
temp_insert = "insert into temperature values (%(time)s, %(id)s, %(temp)s, %(battery)s)"

dbconfig = {
  "host": "XXXXXXXX",
  "database": "XXXXXXXX",
  "user":     "XXXXXXXX",
  "password": "XXXXXXXX"
}
db = pymysql.connect(**dbconfig)
cursor = db.cursor()

proc = subprocess.Popen(['/usr/bin/rtl_433','-F','json','-q'],stdout=subprocess.PIPE)
while True:
  line = proc.stdout.readline()
  if line != '':
    data = json.loads(line.rstrip())
    if data['id'] == 245:
      temp_data = {
        "time": data['time'],
        "id": data['id'],
        "temp": data['temperature_C'],
        "battery": data['battery_ok']
      }
      try:
        cursor.execute(temp_insert, temp_data)
        db.commit()
        print("OS Temperature: {0}".format(data['temperature_C']))
      except pymysql.err.IntegrityError as err:
          # Bastard sent 2
          pass
      except Exception as err:
        print("Generic temp error: {0}".format(type(err)))
    elif data['id'] == 145433412:
      oil_data = {
        "time": data['time'],
        "temp": data['temperature_C'],
        "depth": data['depth']
      }
      try:
        cursor.execute(oil_insert, oil_data)
        db.commit()
        print("Tank: depth: {0} temp: {1}".format(data['depth'], data['temperature_C']))
      except Exception as err:
        print("Generic oil error: {0}".format(type(err)))
    else:
      # Database gone away
      print(f"Dafuq? {json.dumps(data)}")
  else:
    break
  sys.stdout.flush()

cursor.close()
db.close()

