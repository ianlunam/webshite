#!/usr/bin/python

import subprocess
import json
import mysql.connector

oil_insert = "insert into oil values (%(time)s, %(temp)s, %(depth)s, 0)"
temp_insert = "insert into history values (%(time)s, %(temp)s, 0, 0)"

dbconfig = {
  "host": "192.168.0.244",
  "database": "temps",
  "user":     "root"
}
db = mysql.connector.connect(**dbconfig)
cursor = db.cursor()

proc = subprocess.Popen(['/usr/local/bin/rtl_433','-F','json','-q'],stdout=subprocess.PIPE)
while True:
  line = proc.stdout.readline()
  if line != '':
    data = json.loads(line.rstrip())
    if data['id'] == 119:
      temp_data = {
        "time": data['time'],
        "temp": data['temperature_C']
      }
      try:
        cursor.execute(temp_insert, temp_data)
        db.commit()
      except Exception as err:
        pass
    elif data['id'] == 145433412:
      oil_data = {
        "time": data['time'],
        "temp": data['temperature_C'],
        "depth": data['depth']
      }
      cursor.execute(oil_insert, oil_data)
      db.commit()
    else:
      print "Unknown: " + data
  else:
    break

cursor.close()
db.close()
