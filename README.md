# gtGraph
We are developing a graph library as part of Grokit system https://github.com/tera-insights

Currently implemented graph algorithms are:

1. connected components following GLA style

optimization:
1. use mct instead of unordered_map
   mct allocates memory for 1000 items during each memeory allocation instead of one by one as unordered_map does. This is important for multi-thread programming as memeory allocation needs lock

2. during merge stage, make sure "this" is the bigger one.

