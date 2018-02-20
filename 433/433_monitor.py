#!/usr/bin/python

import subprocess
import json
import mysql.connector
import sys

oil_insert = "insert into oiltank values (%(time)s, %(temp)s, %(depth)s)"
temp_insert = "insert into temperature values (%(time)s, %(id)s, %(temp)s, %(battery)s)"

dbconfig = {
  "host": "192.168.0.244",
  "database": "temps",
  "user":     "root"
}
db = mysql.connector.connect(**dbconfig)
db.ping(True)
cursor = db.cursor()

proc = subprocess.Popen(['/usr/local/bin/rtl_433','-F','json','-q'],stdout=subprocess.PIPE)
while True:
  line = proc.stdout.readline()
  if line != '':
    data = json.loads(line.rstrip())
    if data['id'] == 119:
      temp_data = {
        "time": data['time'],
        "id": data['id'],
        "temp": data['temperature_C'],
        "battery": data['battery']
      }
      try:
        cursor.execute(temp_insert, temp_data)
        db.commit()
        print "OS Temperature: {0}".format(data['temperature_C'])
      except mysql.connector.errors.IntegrityError:
        pass
      except mysql.connector.errors.OperationalError:
        print "Reconnecting"
        try:
          db.reconnect()
        except Exception:
          pass
      except Exception as err:
        print "Generic temp error: {0}".format(type(err))
    elif data['id'] == 145433412:
      oil_data = {
        "time": data['time'],
        "temp": data['temperature_C'],
        "depth": data['depth']
      }
      try:
        cursor.execute(oil_insert, oil_data)
        db.commit()
        print "Tank: depth: {0} temp: {1}".format(data['depth'], data['temperature_C'])
      except mysql.connector.errors.OperationalError:
        print "Reconnecting"
        try:
          db.reconnect()
        except Exception:
          pass
      except Exception as err:
        print "Generic oil error: {0}".format(type(err))
    else:
      print "Dafuq? " + "".join(['{0}: {1} '.format(k, v) for k,v in data.iteritems()])
  else:
    break
  sys.stdout.flush()

cursor.close()
db.close()
