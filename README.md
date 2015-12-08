# gtGraph
We are developing a graph library as part of Grokit system https://github.com/tera-insights. 

Currently implemented graph algorithms is finding connected components in large graph.

I About Grokit

Grokit (http://www.terainsights.com/) is data engine for large scale data processing. It provides several computational models for large scale data analysis, and one of them is Generalized Linear Aggregate (GLA). GLA provides four APIs to users,       AddItem, AddState, Finalize, GetNextResult. And AddItem and AddState works in spirit similar with Map/Reduce. 

II Connected Component Algorithm

Since the Grokit system is deployed on a machine with 512G memory, we used union-find data structure to find connected components. The algorithm contains three phases:

Phase 1: scan the graph in parallel and run a simple parallel GLA to figure out some metadata about the graph, for example, the number of nodes. Allocate memeory for the union-find data structure.

Phase 2: scan the graph in parallel and run union-find GLA in parallel to find connected components. It is noted that no lick is used in this stage.

Phase 3: Repeat Phase 2 until convergence. The reason is that no lock is used in Phase 2 which use 64 threads to uodate a shared data structure, data race might happen. However, in our current experiments, it never happened. As a result, Phase 3 acts like a safe check.

Note that we scan the dataset which lives on disk sevral times which is usually a bad idea. The reason is that Grokit a data centric processing engine, which load data in extreamly high speed, 4GB/seconds. In fact, when we design algorithm, the goal is to keep up with disk speed. For details, please refer to Datapath.

Arumugam, Subi, et al. "The DataPath system: a data-centric analytic processing engine for large data warehouses." Proceedings of the 2010 ACM SIGMOD International Conference on Management of data. ACM, 2010.

III Results

   (some details about background introduction of dataset)
   Two dataset are tested, Graph, which contains 1,724,573,717 nodes and 64,422,807,961 edges, and Graph2012, which contains 3,563,602,788 nodes and 128,736,914,167 edges. For Graph, the algorithm takes 10 minutes; For Graph2012, it takes 12 minutes.
Some interesting findings:

1). Super large component.
   In both graphs, we find that there exists a super large connected component.
   ![alt tag](https://github.com/fujun-liu/gtGraph/blob/master/largeComponent.png)

2). The number of connected components dcrease in log10 scale.
   In the figure below, we count the number of connected components whose sizes are above a certain threshold. Note that, the y-axis is log10 based.
    ![alt tag](https://github.com/fujun-liu/gtGraph/blob/master/change.png)

