Comp <- function(data, inputs, outputs) {
    inputs <- convert.exprs(substitute(inputs))
    outputs <- convert.atts(substitute(outputs))
    gla <- GLA(graph::ConnectedComponentsPoolAlloc)
    Aggregate(data, gla, inputs, outputs)
}

