import os, sys
#Add FoxTelem Jar to Path.  Need to parameterize this.  For a later day.
foxtelem_jar_path = os.path.join(os.getcwd(), 'FoxTelem.jar')
sys.path.append(foxtelem_jar_path)

import csv, glob
from argparse import ArgumentParser
from pprint import pprint
from datetime import datetime
from java.io import File
from common import Config, FoxSpacecraft, Spacecraft
from telemetry import BitArrayLayout, FoxFramePart, PayloadMaxValues, PayloadMinValues, PayloadRtValues

class AmsatTelemetryDataMigrator:

    def __init__(self, spacecraft_dir=None, data_dir=None):
        self.spacecraft_dir  = spacecraft_dir if spacecraft_dir is not None else os.path.join(os.getcwd(), 'spacecraft')
        self.master_files    = glob.glob(os.path.join(self.spacecraft_dir, "*.MASTER"))
        self.data_path       = data_dir if data_dir is not None else os.path.join(os.getcwd(), 'data')
        self.foxdb_path      = os.path.join(self.data_path, 'FOXDB')
        self.serverlogs_path = os.path.join(self.data_path, 'serverlogs')
        self.user_filename   = os.path.join(os.getcwd(), 'userfile.dat')

        self.spacecraft = {}

    def load_spacecraft(self, name, refresh=False):
        if name in self.spacecraft and not refresh:
            return self.spacecraft[name]

        master_filename = "{}_fm.MASTER".format(name)
        master_path = os.path.join(self.spacecraft_dir, master_filename)

        master_file = File(master_path)
        user_file   = File(self.user_filename)
        
        self.spacecraft[name] = FoxSpacecraft(master_file, user_file)

        return self.spacecraft[name]

    def get_serverlog_fieldnames(self, layout):
        return ['captureDate', 'id', 'resets', 'uptime', 'type'] + [s for s in layout.fieldName] 

    def get_serverlog_filename(self, fox, layout):
        name = "{}{}{}.log".format(fox.series, fox.foxId, getattr(fox, "{}_LAYOUT".format(layout)))

        return os.path.join(self.serverlogs_path, name)

    def get_payload_object(self, layout_name, layout):
        if layout_name == "REAL_TIME":
            return PayloadRtValues(layout)
        elif layout_name == "MIN":
            return PayloadMinValues(layout)
        elif layout_name == "MAX":
            return PayloadMaxValues(layout)
        else:
            raise ValueError("Layout Name {} does not exist or is not yet supported by the tool.".format(layout_name))

    """
    Process Serverlog File
    fox - FoxSpacecraft Object
    layout_name - "REAL_TIME", "MIN" or "MAX"
    """
    def process_serverlog(self, fox, layout_name):
        if layout_name not in ["REAL_TIME", "MIN", "MAX"]:
            raise ValueError("Layout Name {} does not exist or is not yet supported by the tool.".format(layout_name))

        layout = fox.getLayoutByName(getattr(Spacecraft, "{}_LAYOUT".format(layout_name)))

        data_filename = self.get_serverlog_filename(fox, layout_name)

        data_fp = open(data_filename, 'r')
        reader = csv.DictReader(data_fp, fieldnames=self.get_serverlog_fieldnames(layout))

        for row in reader:
            payload = self.get_payload_object(layout_name, layout)

            row['captureDate'] = datetime.strptime(row['captureDate'], "%Y%m%d%H%M%S")

            for field in layout.fieldName:
                #TODO Other Status, Message, On/Off, etc messages aren't yet decoded.  Other functions appear to do this.
                row[field] = payload.convertRawValue(field, int(row[field]), layout.getConversionByName(field), fox)

            yield row

        data_fp.close()

    def process_serverlog_to_csv(self, fox, layout_name, output_filename):
        output_fp = open(output_filename, 'w')
        layout = fox.getLayoutByName(getattr(Spacecraft, "{}_LAYOUT".format(layout_name)))
        writer = csv.DictWriter(output_fp, fieldnames=self.get_serverlog_fieldnames(layout))
        writer.writeheader()

        for row in self.process_serverlog(fox, layout_name):
            writer.writerow(row)

        output_fp.close()

if __name__ == "__main__":
    ap = ArgumentParser()

    ap.add_argument('spacecraft', help="Spacecraft Name (ie. FOX1D)")
    ap.add_argument('-l', '--layout', help="REAL_TIME, MIN or MAX")
    ap.add_argument('-o', '--output', help="Output Filename")

    args = ap.parse_args()

    mig = AmsatTelemetryDataMigrator()

    fox = mig.load_spacecraft(args.spacecraft)

    mig.process_serverlog_to_csv(fox, args.layout, args.output)

