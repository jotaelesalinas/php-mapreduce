# php-local-mapreduce example

In this example, we take insurance data from the state of Florida.

Sample data downloaded from [SpatialKey](http://support.spatialkey.com/spatialkey-sample-csv-data/). From their website:

> The sample insurance file contains 36,634 records in Florida for 2012 from a sample company that implemented an agressive growth plan in 2012.  There are total insured value (TIV) columns containing TIV from 2011 and 2012, so this dataset is great for testing out the comparison feature.  This file has address information that you can choose to geocode, or you can use the existing latitude/longitude in the file.

For each policy, we have:

- policy id
- state code
- county
- total insured value 2011
- total insured value 2012
- latitude/longitude
  
We are going to compute, for each county:

- number of policies
- average latitude/longitude

and export the data to a CSV file and a Google Maps KML file.

### TO DO

- [ ] compute total insured value 2011
- [ ] compute average insured value 2011
- [ ] compute total insured value 2012
- [ ] compute average insured value 2012
- [ ] compute growth in %
