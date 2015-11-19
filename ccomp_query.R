library(gtGraph)

data <- Load(Graph)
## data <- ReadCSV("/data/graph/test.csv", c(S = INT, T = INT))

agg <- CComp(data, c(S, T), c(VertexID, CompID))
View(agg)
